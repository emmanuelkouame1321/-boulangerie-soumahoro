<?php

declare(strict_types=1);

namespace App\Support;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Exports sans dépendance (Design System §84).
 *
 *  - `csv()` : CSV natif, ouvrable par tous les tableurs ;
 *  - `xls()`  : classeur SpreadsheetML 2003 (XML), ouvrable par Excel et
 *               LibreOffice, sans paquet tiers.
 *
 * Le rendu PDF s'obtient par une vue imprimable côté navigateur (voir
 * `reports/print`) : aucune librairie PDF n'est embarquée (ADR-020).
 */
final class ReportExport
{
    /**
     * @param  list<string>  $headers
     * @param  iterable<int, array<int, int|float|string|null>>  $rows
     */
    public static function csv(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                return;
            }

            // BOM UTF-8 : Excel reconnaît les accents.
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headers, ';');

            foreach ($rows as $row) {
                fputcsv($handle, array_map(static fn ($value): string => (string) $value, $row), ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Classeur SpreadsheetML 2003 — pas de dépendance, ouvert par Excel.
     *
     * @param  list<string>  $headers
     * @param  iterable<int, array<int, int|float|string|null>>  $rows
     */
    public static function xls(string $filename, string $title, array $headers, iterable $rows): Response
    {
        $escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<?mso-application progid="Excel.Sheet"?>'."\n";
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" '
            .'xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">'."\n";
        $xml .= '<Worksheet ss:Name="'.mb_substr($title, 0, 31).'"><Table>'."\n";

        $xml .= '<Row>';
        foreach ($headers as $header) {
            $xml .= '<Cell><Data ss:Type="String">'.$escape((string) $header).'</Data></Cell>';
        }
        $xml .= '</Row>'."\n";

        foreach ($rows as $row) {
            $xml .= '<Row>';
            foreach ($row as $value) {
                $type = is_int($value) || is_float($value) ? 'Number' : 'String';
                $xml .= '<Cell><Data ss:Type="'.$type.'">'.$escape((string) $value).'</Data></Cell>';
            }
            $xml .= '</Row>'."\n";
        }

        $xml .= '</Table></Worksheet></Workbook>';

        return response($xml, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
