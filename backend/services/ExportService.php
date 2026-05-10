<?php

namespace Services;

use Core\Database;

class ExportService
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function exportWatchlistCsv(int $userId): void
    {
        $rows = $this->db->query(
            'SELECT mc.title, mc.release_date, mc.vote_average,
                    w.watched, w.added_at, w.watched_at
             FROM watchlist w
             JOIN movies_cache mc ON mc.tmdb_id = w.tmdb_id
             WHERE w.user_id = ?
             ORDER BY w.added_at DESC',
            [$userId]
        )->fetchAll();

        $this->outputCsv($rows, [
            'Titulo', 'Data de Lancamento', 'Nota TMDB',
            'Assistido', 'Adicionado Em', 'Assistido Em',
        ], 'watchlist');
    }

    public function exportRatingsCsv(int $userId): void
    {
        $rows = $this->db->query(
            'SELECT mc.title, mc.release_date, r.score, r.review, r.created_at
             FROM ratings r
             JOIN movies_cache mc ON mc.tmdb_id = r.tmdb_id
             WHERE r.user_id = ?
             ORDER BY r.created_at DESC',
            [$userId]
        )->fetchAll();

        $this->outputCsv($rows, [
            'Titulo', 'Data de Lancamento', 'Nota', 'Critica', 'Data de Avaliacao',
        ], 'avaliacoes');
    }

    public function exportFavoritesCsv(int $userId): void
    {
        $rows = $this->db->query(
            'SELECT mc.title, mc.release_date, mc.vote_average, f.added_at
             FROM favorites f
             JOIN movies_cache mc ON mc.tmdb_id = f.tmdb_id
             WHERE f.user_id = ?
             ORDER BY f.added_at DESC',
            [$userId]
        )->fetchAll();

        $this->outputCsv($rows, [
            'Titulo', 'Data de Lancamento', 'Nota TMDB', 'Adicionado Em',
        ], 'favoritos');
    }

    public function exportRatingsPdf(int $userId, string $userName): void
    {
        $rows = $this->db->query(
            'SELECT mc.title, mc.release_date, r.score, r.review, r.created_at
             FROM ratings r
             JOIN movies_cache mc ON mc.tmdb_id = r.tmdb_id
             WHERE r.user_id = ?
             ORDER BY r.score DESC',
            [$userId]
        )->fetchAll();

        $lines = [
            'OOLD - Relatorio de Avaliacoes',
            'Utilizador: ' . $userName,
            'Gerado em: ' . date('d/m/Y'),
            '',
        ];

        if (!$rows) {
            $lines[] = 'Sem avaliacoes para exportar.';
        }

        foreach ($rows as $row) {
            $title = $row['title'] ?? 'Sem titulo';
            $release = $row['release_date'] ?: '-';
            $score = $row['score'] ?? '-';
            $review = trim((string)($row['review'] ?? ''));
            $created = $row['created_at'] ?? '-';

            $lines[] = $title;
            $lines[] = 'Lancamento: ' . $release . ' | Nota: ' . $score . '/10 | Data: ' . $created;
            if ($review !== '') {
                $lines[] = 'Critica: ' . $review;
            }
            $lines[] = '';
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="avaliacoes_' . date('Y-m-d') . '.pdf"');
        header('Cache-Control: no-cache, must-revalidate');

        echo $this->buildSimplePdf($lines);
        exit;
    }

    private function outputCsv(array $rows, array $headers, string $filename): void
    {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '_' . date('Y-m-d') . '.csv"');
        header('Pragma: no-cache');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, $headers, ';');

        foreach ($rows as $row) {
            fputcsv($out, array_values($row), ';');
        }

        fclose($out);
        exit;
    }

    private function buildSimplePdf(array $lines): string
    {
        $pages = array_chunk($this->wrapLines($lines, 92), 42);
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '',
        ];
        $pageReferences = [];

        foreach ($pages as $page) {
            $content = $this->buildPdfPageContent($page);
            $contentId = count($objects) + 1;
            $objects[] = "<< /Length " . strlen($content) . " >>\nstream\n{$content}\nendstream";

            $pageId = count($objects) + 1;
            $pageReferences[] = $pageId . ' 0 R';
            $objects[] = "__PAGE__ {$contentId}";
        }

        $fontId = count($objects) + 1;
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        $objects[1] = '<< /Type /Pages /Kids [' . implode(' ', $pageReferences) . '] /Count ' . count($pageReferences) . ' >>';

        foreach ($objects as &$object) {
            if (strpos($object, '__PAGE__ ') === 0) {
                $contentId = (int)substr($object, 9);
                $object = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] /Resources << /Font << /F1 {$fontId} 0 R >> >> /Contents {$contentId} 0 R >>";
            }
        }
        unset($object);

        return $this->assemblePdf($objects);
    }

    private function wrapLines(array $lines, int $maxLength): array
    {
        $wrapped = [];
        foreach ($lines as $line) {
            $line = $this->pdfText((string)$line);
            if ($line === '') {
                $wrapped[] = '';
                continue;
            }

            foreach (explode("\n", wordwrap($line, $maxLength, "\n", true)) as $part) {
                $wrapped[] = $part;
            }
        }

        return $wrapped ?: ['Sem dados para exportar.'];
    }

    private function buildPdfPageContent(array $lines): string
    {
        $content = "BT\n/F1 11 Tf\n50 545 Td\n14 TL\n";
        foreach ($lines as $line) {
            $content .= '(' . $this->escapePdfText($line) . ") Tj\nT*\n";
        }
        return $content . "ET";
    }

    private function assemblePdf(array $objects): string
    {
        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1) . " 0 obj\n{$object}\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xref}\n%%EOF";

        return $pdf;
    }

    private function pdfText(string $text): string
    {
        $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);
        return $converted !== false ? $converted : preg_replace('/[^\x20-\x7E]/', '', $text);
    }

    private function escapePdfText(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }
}
