<?php
// ============================================================
//  Service — ExportService  (CSV e PDF)
// ============================================================

namespace Services;

use Core\Database;

class ExportService
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ----------------------------------------------------------
    //  CSV
    // ----------------------------------------------------------

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
            'Título', 'Data de Lançamento', 'Nota TMDB',
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
            'Título', 'Data de Lançamento', 'Nota', 'Crítica', 'Data de Avaliação',
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
            'Título', 'Data de Lançamento', 'Nota TMDB', 'Adicionado Em',
        ], 'favoritos');
    }

    private function outputCsv(array $rows, array $headers, string $filename): void
    {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '_' . date('Y-m-d') . '.csv"');
        header('Pragma: no-cache');

        $out = fopen('php://output', 'w');
        // BOM para Excel reconhecer UTF-8
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, $headers, ';');

        foreach ($rows as $row) {
            fputcsv($out, array_values($row), ';');
        }

        fclose($out);
        exit;
    }

    // ----------------------------------------------------------
    //  PDF simples (sem dependências — HTML→PDF via output buffer)
    //  Para produção real: use TCPDF, mPDF ou Dompdf
    // ----------------------------------------------------------

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

        header('Content-Type: text/html; charset=UTF-8');
        // A impressão para PDF é feita pelo browser (Ctrl+P → Guardar como PDF)
        // Para PDF server-side real, instale TCPDF via composer
        header('Content-Disposition: inline; filename="avaliacoes_' . date('Y-m-d') . '.html"');
        header('Cache-Control: no-cache, must-revalidate');

        echo $this->buildPdfHtml($rows, $userName);
        exit;
    }

    private function buildPdfHtml(array $rows, string $userName): string
    {
        $date = date('d/m/Y');
        $rowsHtml = '';

        foreach ($rows as $r) {
            $title    = htmlspecialchars($r['title']);
            $release  = $r['release_date'] ?? '-';
            $score    = $r['score'];
            $review   = htmlspecialchars($r['review'] ?? '-');
            $created  = $r['created_at'];
            $stars    = str_repeat('★', (int)($score / 2)) . str_repeat('☆', 5 - (int)($score / 2));

            $rowsHtml .= "<tr>
                <td>{$title}</td>
                <td>{$release}</td>
                <td>{$score}/10 {$stars}</td>
                <td>{$review}</td>
                <td>{$created}</td>
            </tr>";
        }

        return <<<HTML
        <!DOCTYPE html>
        <html lang="pt">
        <head>
          <meta charset="UTF-8">
          <title>Avaliações — OOLD</title>
          <style>
            body { font-family: Arial, sans-serif; font-size: 13px; color: #222; }
            h1   { color: #e50914; }
            table { width: 100%; border-collapse: collapse; margin-top: 16px; }
            th   { background: #e50914; color: #fff; padding: 8px; text-align: left; }
            td   { padding: 6px 8px; border-bottom: 1px solid #ddd; }
            tr:nth-child(even) td { background: #f9f9f9; }
            .meta { color: #666; font-size: 12px; margin-bottom: 8px; }
            @media print { @page { size: A4 landscape; margin: 1cm; } }
          </style>
        </head>
        <body>
          <h1>🎬 OOLD — Relatório de Avaliações</h1>
          <p class="meta">Utilizador: <strong>{$userName}</strong> &nbsp;|&nbsp; Gerado em: {$date}</p>
          <table>
            <thead>
              <tr>
                <th>Título</th><th>Lançamento</th><th>Nota</th><th>Crítica</th><th>Data</th>
              </tr>
            </thead>
            <tbody>{$rowsHtml}</tbody>
          </table>
          <script>window.onload = () => window.print();</script>
        </body>
        </html>
        HTML;
    }
}
