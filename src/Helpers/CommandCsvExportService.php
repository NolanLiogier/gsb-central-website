<?php

namespace App\Helpers;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;

/**
 * Export des commandes au format CSV via PhpSpreadsheet.
 */
class CommandCsvExportService
{
    /**
     * Envoie un fichier CSV des commandes (en-têtes HTTP + corps).
     *
     * @param array $commands Résultat de CommandRepository::getCommandsByUserId (avec clé products).
     */
    public function streamCommandsCsv(array $commands): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headerRow = [
            'ID commande',
            'Date de livraison',
            'Date de création',
            'Statut',
            'Total TTC (EUR)',
        ];
        $sheet->fromArray($headerRow, null, 'A1');

        $dataRows = [];
        foreach ($commands as $command) {
            $dataRows[] = [
                $command['command_id'] ?? '',
                $this->formatDateTime($command['delivery_date'] ?? null),
                $this->formatDateTime($command['created_at'] ?? null),
                $command['status_name'] ?? '',
                round($this->computeTotalTtc($command['products'] ?? []), 2),
            ];
        }
        if ($dataRows !== []) {
            $sheet->fromArray($dataRows, null, 'A2');
        }

        $filename = 'commandes_' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $writer = new Csv($spreadsheet);
        $writer->setDelimiter(';');
        $writer->setLineEnding("\r\n");
        $writer->setUseBOM(true);
        $writer->save('php://output');

        $spreadsheet->disconnectWorksheets();
    }

    /**
     * Total TTC : même règle que ModifyCommandsTemplate (HT × 1,20).
     *
     * @param array $products Lignes avec price et quantity.
     */
    private function computeTotalTtc(array $products): float
    {
        $subtotal = 0.0;
        foreach ($products as $product) {
            $price = (float)($product['price'] ?? 0);
            $quantity = (int)($product['quantity'] ?? 0);
            $subtotal += $price * $quantity;
        }

        return $subtotal * 1.20;
    }

    private function formatDateTime(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return $value;
        }

        return date('d/m/Y H:i', $timestamp);
    }
}
