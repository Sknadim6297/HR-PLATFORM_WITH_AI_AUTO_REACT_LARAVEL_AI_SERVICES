<?php

namespace App\Services\AI;

use App\Exceptions\DocumentExtractionException;
use App\Models\AiDocument;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\Element\AbstractElement;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\IOFactory;
use Smalot\PdfParser\Parser as PdfParser;
use Throwable;

class DocumentTextExtractor
{
    public function extract(AiDocument $document): string
    {
        if (! Storage::disk('local')->exists($document->file_path)) {
            throw new DocumentExtractionException(
                'The document file could not be found.'
            );
        }

        $contents = Storage::disk('local')->get($document->file_path);

        if (! is_string($contents) || $contents === '') {
            throw new DocumentExtractionException(
                'The document is empty or unreadable.'
            );
        }

        $text = match ($this->detectType($document)) {
            'txt' => $this->extractFromTxt($contents),
            'pdf' => $this->extractFromPdf($contents),
            'docx' => $this->extractFromDocx($contents),
            default => throw new DocumentExtractionException(
                'Unsupported document type for text extraction.'
            ),
        };

        $text = trim($text);

        if ($text === '') {
            throw new DocumentExtractionException(
                'No readable text could be extracted from the document.'
            );
        }

        return $text;
    }

    private function detectType(AiDocument $document): string
    {
        $mime = strtolower((string) $document->mime_type);
        $extension = strtolower(pathinfo($document->file_path, PATHINFO_EXTENSION));

        return match (true) {
            $mime === 'application/pdf' || $extension === 'pdf' => 'pdf',
            $mime === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                || $extension === 'docx' => 'docx',
            $mime === 'text/plain' || $extension === 'txt' => 'txt',
            default => 'unknown',
        };
    }

    private function extractFromTxt(string $contents): string
    {
        if (! mb_check_encoding($contents, 'UTF-8')) {
            $converted = mb_convert_encoding(
                $contents,
                'UTF-8',
                'UTF-8, ISO-8859-1, Windows-1252'
            );

            if ($converted === false || $converted === '') {
                throw new DocumentExtractionException(
                    'The text document encoding is not supported.'
                );
            }

            $contents = $converted;
        }

        $withoutBom = preg_replace('/^\xEF\xBB\xBF/', '', $contents);

        return is_string($withoutBom) ? $withoutBom : $contents;
    }

    private function extractFromPdf(string $contents): string
    {
        try {
            $pdf = (new PdfParser)->parseContent($contents);

            return $pdf->getText();
        } catch (DocumentExtractionException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new DocumentExtractionException(
                'Unable to extract text from the PDF document.',
                $exception,
            );
        }
    }

    private function extractFromDocx(string $contents): string
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'ai-docx-');

        if ($tempPath === false) {
            throw new DocumentExtractionException(
                'Unable to process the DOCX document.'
            );
        }

        $docxPath = $tempPath.'.docx';

        if (! @rename($tempPath, $docxPath)) {
            @unlink($tempPath);

            throw new DocumentExtractionException(
                'Unable to process the DOCX document.'
            );
        }

        try {
            if (file_put_contents($docxPath, $contents) === false) {
                throw new DocumentExtractionException(
                    'Unable to process the DOCX document.'
                );
            }

            $phpWord = IOFactory::load($docxPath);
            $parts = [];

            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    $parts = array_merge($parts, $this->collectElementText($element));
                }
            }

            return implode("\n", array_values(array_filter(
                array_map(static fn (string $part): string => trim($part), $parts),
                static fn (string $part): bool => $part !== '',
            )));
        } catch (DocumentExtractionException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new DocumentExtractionException(
                'Unable to extract text from the DOCX document.',
                $exception,
            );
        } finally {
            @unlink($docxPath);
        }
    }

    /**
     * @return list<string>
     */
    private function collectElementText(AbstractElement $element): array
    {
        if ($element instanceof Text) {
            return [$element->getText()];
        }

        if ($element instanceof TextRun) {
            $parts = [];

            foreach ($element->getElements() as $child) {
                $parts = array_merge($parts, $this->collectElementText($child));
            }

            return [implode('', $parts)];
        }

        if (method_exists($element, 'getElements')) {
            $parts = [];

            foreach ($element->getElements() as $child) {
                if ($child instanceof AbstractElement) {
                    $parts = array_merge($parts, $this->collectElementText($child));
                }
            }

            return $parts;
        }

        if (method_exists($element, 'getText')) {
            $text = $element->getText();

            return is_string($text) ? [$text] : [];
        }

        return [];
    }
}
