<?php
/**
 * JC Bytes - Soluções em Tecnologia
 * DIMOB API - Formatador de Arquivos DIMOB
 * 
 * @category  Services
 * @package   DIMOB_API
 * @author    JC Bytes Dev Team <dev@jcbytes.com.br>
 * @copyright 2024 JC Bytes - Soluções em Tecnologia
 * @license   Proprietário
 * @version   1.0.0
 * @link      https://jcbytes.com.br
 */

namespace App\Services;

/**
 * Classe DimobFormatter
 * 
 * Responsável pela formatação dos dados no padrão DIMOB da Receita Federal
 * Desenvolvido por JC Bytes - Soluções em Tecnologia
 */
class DimobFormatter
{
    // Constantes de formatação
    const REGISTRO_R01 = 'R01';
    const REGISTRO_R02 = 'R02';
    const REGISTRO_IR  = 'IR';
    
    const TIPOS_DISTRATO = ['DISTRATO', 'CESSAO_DISTRATO'];
    
    /** @var string */
    private $anoCalendario;
    
    /** @var string */
    private $cnpjEmpresa;
    
    /** @var string */
    private $nomeEmpresa;

    public function format($data)
    {
        try {
            $dimobContent = '';

            // Remove o cabeçalho e extrai informações da empresa
            $header = array_shift($data);
            $this->cnpjEmpresa = $this->cleanNumber($header[0] ?? '');
            $this->anoCalendario = $this->cleanNumber($header[1] ?? date('Y'));
            $this->nomeEmpresa = $this->cleanText($header[2] ?? '');

            // Adiciona registro R01 - Identificação da empresa
            $dimobContent .= $this->formatR01();

            // Adiciona registro R02 - Ano calendário
            $dimobContent .= $this->formatR02();

            // Processa os registros de imóveis
            foreach ($data as $row) {
                // Verifica se a linha tem dados
                if (empty(array_filter($row))) {
                    continue;
                }

                // Formata registro IR
                $dimobContent .= $this->formatIR($row);
            }

            return $dimobContent;

        } catch (\Exception $e) {
            throw new \Exception('Erro ao formatar dados para DIMOB: ' . $e->getMessage());
        }
    }

    private function formatR01()
    {
        $line = self::REGISTRO_R01;
        $line .= str_pad($this->cleanNumber($this->cnpjEmpresa), 14, '0', STR_PAD_LEFT);
        $line .= str_pad(substr($this->cleanText($this->nomeEmpresa), 0, 60), 60, ' ', STR_PAD_RIGHT);
        $line .= str_repeat(' ', 23);
        $line .= "\r\n";
        return $line;
    }

    private function formatR02()
    {
        $line = self::REGISTRO_R02;
        $line .= str_pad($this->cleanNumber($this->anoCalendario), 4, '0', STR_PAD_LEFT);
        $line .= str_repeat(' ', 93);
        $line .= "\r\n";
        return $line;
    }

    private function formatIR($row)
    {
        $line = self::REGISTRO_IR;
        
        // CNPJ da empresa (14 posições)
        $line .= str_pad($this->cleanNumber($row[0] ?? ''), 14, '0', STR_PAD_LEFT);
        
        // Ano calendário (4 posições)
        $line .= str_pad($this->cleanNumber($this->anoCalendario), 4, '0', STR_PAD_LEFT);
        
        // CPF/CNPJ do comprador (14 posições)
        $line .= str_pad($this->cleanNumber($row[2] ?? ''), 14, '0', STR_PAD_LEFT);
        
        // Nome do comprador (40 posições)
        $line .= str_pad(substr($this->cleanText($row[3] ?? ''), 0, 40), 40, ' ', STR_PAD_RIGHT);
        
        // Número do contrato (20 posições)
        $line .= str_pad($this->cleanText($row[4] ?? ''), 20, ' ', STR_PAD_RIGHT);
        
        // Data da operação (8 posições - AAAAMMDD)
        $line .= $this->formatDate($row[5] ?? '');
        
        // Valor da operação (14 posições)
        $line .= str_pad($this->formatValue($row[6] ?? '0', $row[7] ?? ''), 14, '0', STR_PAD_LEFT);
        
        $line .= "\r\n";
        return $line;
    }

    private function cleanNumber($value)
    {
        return preg_replace('/[^0-9]/', '', $value);
    }

    private function cleanText($value)
    {
        $value = preg_replace('/[^a-zA-Z0-9\s]/', '', $this->removeAccents($value));
        return strtoupper(trim($value));
    }

    private function removeAccents($string)
    {
        if (!preg_match('/[\x80-\xff]/', $string)) {
            return $string;
        }

        $chars = array(
            chr(195).chr(128) => 'A', chr(195).chr(129) => 'A',
            chr(195).chr(130) => 'A', chr(195).chr(131) => 'A',
            chr(195).chr(132) => 'A', chr(195).chr(133) => 'A',
            chr(195).chr(135) => 'C', chr(195).chr(136) => 'E',
            chr(195).chr(137) => 'E', chr(195).chr(138) => 'E',
            chr(195).chr(139) => 'E', chr(195).chr(140) => 'I',
            chr(195).chr(141) => 'I', chr(195).chr(142) => 'I',
            chr(195).chr(143) => 'I', chr(195).chr(145) => 'N',
            chr(195).chr(146) => 'O', chr(195).chr(147) => 'O',
            chr(195).chr(148) => 'O', chr(195).chr(149) => 'O',
            chr(195).chr(150) => 'O', chr(195).chr(153) => 'U',
            chr(195).chr(154) => 'U', chr(195).chr(155) => 'U',
            chr(195).chr(156) => 'U', chr(195).chr(157) => 'Y',
            chr(195).chr(159) => 's', chr(195).chr(160) => 'a',
            chr(195).chr(161) => 'a', chr(195).chr(162) => 'a',
            chr(195).chr(163) => 'a', chr(195).chr(164) => 'a',
            chr(195).chr(165) => 'a', chr(195).chr(167) => 'c',
            chr(195).chr(168) => 'e', chr(195).chr(169) => 'e',
            chr(195).chr(170) => 'e', chr(195).chr(171) => 'e',
            chr(195).chr(172) => 'i', chr(195).chr(173) => 'i',
            chr(195).chr(174) => 'i', chr(195).chr(175) => 'i',
            chr(195).chr(177) => 'n', chr(195).chr(178) => 'o',
            chr(195).chr(179) => 'o', chr(195).chr(180) => 'o',
            chr(195).chr(181) => 'o', chr(195).chr(182) => 'o',
            chr(195).chr(182) => 'o', chr(195).chr(185) => 'u',
            chr(195).chr(186) => 'u', chr(195).chr(187) => 'u',
            chr(195).chr(188) => 'u', chr(195).chr(189) => 'y',
            chr(195).chr(191) => 'y'
        );

        return strtr($string, $chars);
    }

    private function formatDate($date)
    {
        if (empty($date)) {
            return str_repeat('0', 8);
        }

        try {
            // Tenta converter a data para o formato AAAAMMDD
            $timestamp = strtotime($date);
            if ($timestamp === false) {
                return str_repeat('0', 8);
            }
            return date('Ymd', $timestamp);
        } catch (\Exception $e) {
            return str_repeat('0', 8);
        }
    }

    private function formatValue($value, $operationType)
    {
        if (empty($value)) {
            return str_repeat('0', 14);
        }

        try {
            // Remove caracteres não numéricos exceto ponto e vírgula
            $value = preg_replace('/[^0-9,.-]/', '', $value);
            $value = str_replace(',', '.', $value);
            
            // Converte para centavos
            $cents = round((float)$value * 100);
            
            // Se for DISTRATO ou CESSAO_DISTRATO, garante que o valor seja negativo
            if (in_array(strtoupper($operationType), self::TIPOS_DISTRATO)) {
                $cents = -abs($cents);
            }
            
            // Formata com 14 posições, preenchendo com zeros à esquerda
            return str_pad((string)$cents, 14, '0', STR_PAD_LEFT);
        } catch (\Exception $e) {
            return str_repeat('0', 14);
        }
    }
}
