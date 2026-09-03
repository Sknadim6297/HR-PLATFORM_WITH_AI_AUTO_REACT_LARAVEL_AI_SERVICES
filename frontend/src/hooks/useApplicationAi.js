import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import * as aiApi from '../api/ai'
import { deriveAiUiState } from '../constants/ai'

const POLL_MS = 5000
const MAX_POLLS = 36 // ~3 minutes

/**
 * Uses AI data already embedded on the application resource.
 * Polls document + application refresh only while processing.
 */
export function useApplicationAi(application, { refreshApplication, enabled = true } = {}) {
  const [resumeDocument, setResumeDocument] = useState(null)
  const [documentLoading, setDocumentLoading] = useState(false)
  const [documentError, setDocumentError] = useState('')
  const [screening, setScreening] = useState(null)
  const [screeningMeta, setScreeningMeta] = useState(null)
  const [screeningLoading, setScreeningLoading] = useState(false)
  const [screeningError, setScreeningError] = useState('')
  const pollCount = useRef(0)
  const refreshRef = useRef(refreshApplication)

  useEffect(() => {
    refreshRef.current = refreshApplication
  }, [refreshApplication])

  const analysis = application?.resume_analysis || null
  const match = application?.job_match || null
  const hasResume = Boolean(application?.resume_document_id)

  const uiState = useMemo(
    () =>
      deriveAiUiState({
        hasResume,
        documentStatus: resumeDocument?.status,
        analysis,
        match,
      }),
    [hasResume, resumeDocument?.status, analysis, match],
  )

  const isProcessing = uiState === 'processing' || uiState === 'processing_match' || uiState === 'pending'

  const loadDocument = useCallback(async () => {
    const docId = application?.resume_document_id
    if (!docId || !enabled) {
      setResumeDocument(null)
      return null
    }
    try {
      const response = await aiApi.getDocument(docId)
      setResumeDocument(response.data)
      setDocumentError('')
      return response.data
    } catch (err) {
      setResumeDocument(null)
      setDocumentError(
        err.normalized?.status === 403
          ? "You don't have permission to view this resume document."
          : err.normalized?.message || 'Unable to load resume document status.',
      )
      return null
    }
  }, [application?.resume_document_id, enabled])

  useEffect(() => {
    if (!enabled || !application?.resume_document_id) {
      const tick = window.setTimeout(() => {
        setResumeDocument(null)
        setDocumentLoading(false)
        setDocumentError('')
      }, 0)
      return () => window.clearTimeout(tick)
    }

    let cancelled = false
    const tick = window.setTimeout(() => {
      if (cancelled) return
      setDocumentLoading(true)
      void loadDocument().finally(() => {
        if (!cancelled) setDocumentLoading(false)
      })
    }, 0)

    return () => {
      cancelled = true
      window.clearTimeout(tick)
    }
  }, [application?.resume_document_id, application?.id, enabled, loadDocument])

  useEffect(() => {
    if (!enabled || !application?.id || !application?.resume_document_id) return undefined
    if (!isProcessing) {
      pollCount.current = 0
      return undefined
    }

    let cancelled = false
    let timer

    async function poll() {
      if (cancelled) return
      if (window.document.hidden) {
        timer = window.setTimeout(poll, POLL_MS)
        return
      }
      if (pollCount.current >= MAX_POLLS) return
      pollCount.current += 1

      await loadDocument()
      if (refreshRef.current) {
        await refreshRef.current()
      }

      if (!cancelled && pollCount.current < MAX_POLLS) {
        timer = window.setTimeout(poll, POLL_MS)
      }
    }

    timer = window.setTimeout(poll, POLL_MS)

    return () => {
      cancelled = true
      window.clearTimeout(timer)
    }
  }, [enabled, application?.id, application?.resume_document_id, isProcessing, loadDocument])

  const runScreening = useCallback(async () => {
    if (!application?.id) return null
    setScreeningLoading(true)
    setScreeningError('')
    try {
      const response = await aiApi.runAiScreening(application.id)
      setScreening(response.data)
      setScreeningMeta(response.meta || null)
      return response
    } catch (err) {
      const status = err.normalized?.status
      let message = err.normalized?.message || 'AI screening is temporarily unavailable.'
      if (status === 403 || status === 404) {
        message = "You don't have permission to run AI screening."
      } else if (status === 429) {
        message = 'AI service is busy. Please try again later.'
      } else if (status === 502 || status === 500) {
        message = 'AI assessment is temporarily unavailable.'
      } else if (status === 422) {
        message = err.normalized?.message || 'AI screening cannot run yet.'
      }
      setScreeningError(message)
      throw err
    } finally {
      setScreeningLoading(false)
    }
  }, [application])

  const refresh = useCallback(async () => {
    await loadDocument()
    if (refreshRef.current) await refreshRef.current()
  }, [loadDocument])

  return {
    document: resumeDocument,
    documentLoading,
    documentError,
    analysis,
    match,
    uiState,
    isProcessing,
    screening,
    screeningMeta,
    screeningLoading,
    screeningError,
    runScreening,
    refresh,
    clearScreeningError: () => setScreeningError(''),
  }
}
