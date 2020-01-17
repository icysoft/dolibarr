<?php

/**
 */
include_once DOL_DOCUMENT_ROOT . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use Luracast\Restler\RestException;

/**
 * API class for receive files
 *
 * @access public
 * @class CreateSpreadsheet {@requires user,external}
 */
class CreateSpreadsheet
{
    public function __construct()
    {
    }

    public function write($type, $path, $objets_ret)
    {
        global $conf, $langs;
        $filepath = DOL_DATA_ROOT . $path;
        $pathtab = $this->createPath($path);
        switch ($type) {
            case 'facturations':
                $spreadsheet = $this->createExportFacturations($objets_ret);
                break;
            case 'encaissements':
                $spreadsheet = $this->createExportEncaissements($objets_ret);
                break;
            default:
                throw new RestException(400, "Wrong field format");
                break;
        }
        $writer = new Xlsx($spreadsheet);
        $writer->save($filepath);
        $file_content = file_get_contents($filepath);
        $mimetype = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        $filename = $this->sanitizePath($pathtab[count($pathtab) - 1]);
        $filesize = filesize($filepath);
        unlink($filepath);
        // return $file_content;
        return array('filename' => $filename, 'content-type' => $mimetype, 'filesize' => $filesize, 'content' => base64_encode($file_content), 'langcode' => $langs->defaultlang, 'encoding' => 'base64');
    }

    private function createExportFacturations($objets_ret)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $arrayData = [
            ['Numéro de facture', 'Date de facture', 'Montant TTC', 'Montant HT', 'Taux TVA', 'Montant TVA', 'Nom du tiers (référence)', 'Libellé de la facture']
        ];
        if ($objets_ret) {
            foreach ($objets_ret as $facture) {
                if ($facture && $facture->lines) {
                    foreach ($facture->lines as $line) {
                        $temptab = [];
                        array_push($temptab, $facture->ref);
                        array_push($temptab, $this->formatDateTimestamp($facture->date_validation));
                        array_push($temptab, floor($line->total_ttc * 100) / 100);
                        array_push($temptab, floor($line->total_ht * 100) / 100);
                        array_push($temptab, floor($line->tva_tx * 100) / 100);
                        array_push($temptab, floor($line->total_tva * 100) / 100);
                        array_push($temptab, $facture->array_options['options_client']);
                        array_push($temptab, $facture->array_options['options_titre']);
                        array_push($arrayData, $temptab);
                    }
                }
            }
        }
        $sheet->fromArray($arrayData, NULL, 'A1');
        return $spreadsheet;
    }

    private function createExportEncaissements($objets_ret)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $arrayData = [
            ['Numéro de facture', "Date d'encaissement", 'Montant TTC', 'Nom du client (référence)', 'Mode de règlement', "Libellé de la facture"]
        ];
        if ($objets_ret) {
            foreach ($objets_ret as $encaissement) {
                $temptab = [];
                array_push($temptab, $encaissement->ref);
                array_push($temptab, $this->formatDateTimestamp($encaissement->datem));
                array_push($temptab, floor($encaissement->total_ttc * 100) / 100);
                array_push($temptab, $encaissement->array_options['options_client']);
                array_push($temptab, $encaissement->mode_reglement_code);
                array_push($temptab, $encaissement->array_options['options_titre']);
                array_push($arrayData, $temptab);
            }
        }
        $sheet->fromArray($arrayData, NULL, 'A1');
        return $spreadsheet;
    }

    private function sanitizePath($str)
    {
        $unwanted_array = array(
            'Š' => 'S', 'š' => 's', 'Ž' => 'Z', 'ž' => 'z', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'A', 'Ç' => 'C', 'È' => 'E', 'É' => 'E',
            'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U',
            'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Þ' => 'B', 'ß' => 'Ss', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'a', 'ç' => 'c',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ð' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
            'ö' => 'o', 'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ý' => 'y', 'þ' => 'b', 'ÿ' => 'y', '/\s+/' => '_', ' ' => '_'
        );
        return strtr($str, $unwanted_array);
    }

    private function createPath($path)
    {
        $pathconstruct = '';
        $i = 0;
        $pathtab = preg_split('[/]', $path);
        $tabcount = count($pathtab);
        foreach ($pathtab as $pathtemp) {
            if ($i < $tabcount - 1 && $pathtemp !== '') {
                $pathconstruct .= '/' . $pathtemp;
                if (!dol_is_dir($this->sanitizePath(DOL_DATA_ROOT . $pathconstruct))) {
                    mkdir($this->sanitizePath(DOL_DATA_ROOT . $pathconstruct), 0700, true);
                }
            }
            $i++;
        }
        return $pathtab;
    }

    private function formatDateTimestamp($timestamp)
    {
        $date = date('d/m/Y', $timestamp);
        return $date;
    }
}
