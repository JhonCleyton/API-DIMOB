<?php
/**
 * JC Bytes - Soluções em Tecnologia
 * DIMOB API - Leitor de Arquivos
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

use PhpOffice\PhpSpreadsheet\IOFactory;
use JCBytes\Exceptions\FileProcessingException;

/**
 * Classe FileReader
 * 
 * Responsável pela leitura e processamento de arquivos CSV e XLSX
 * Desenvolvido por JC Bytes - Soluções em Tecnologia
 */
class FileReader
{
    // Índices das colunas no arquivo
    const COL_CNPJ          = 0;
    const COL_ANO           = 1;
    const COL_RAZAO_SOCIAL  = 2;
    const COL_NOME_FANTASIA = 3;
    
    // Índices dos dados do contrato
    const IDX_CNPJ_LOTEADORA = 1;
    const IDX_CPF_COMPRADOR  = 3;
    const IDX_NOME_COMPRADOR = 4;
    const IDX_DATA_VENDA     = 13;
    const IDX_VALOR_VENDA    = 12;
    const IDX_TIPO_OPERACAO  = 0;

    /**
     * Processa o arquivo e retorna os dados formatados
     * 
     * @param string $filepath Caminho do arquivo
     * @return array
     * @throws FileProcessingException
     */
    public function read($filepath)
    {
        try {
            $spreadsheet = IOFactory::load($filepath);
            $worksheet = $spreadsheet->getActiveSheet();
            $data = $worksheet->toArray();

            // Remove linhas vazias
            $data = array_filter($data, function($row) {
                return !empty(array_filter($row, function($cell) {
                    return $cell !== null && $cell !== '';
                }));
            });

            // Reindexar array
            $data = array_values($data);

            // Extrai cabeçalho com dados da empresa (primeiras duas linhas)
            $header = [
                $data[1][self::COL_CNPJ] ?? '', // CNPJ
                $data[1][self::COL_ANO] ?? date('Y'), // Ano calendário
                $data[1][self::COL_RAZAO_SOCIAL] ?? '', // Razão social
                $data[1][self::COL_NOME_FANTASIA] ?? '' // Nome fantasia
            ];

            // Prepara os dados dos contratos
            $contracts = [];
            for ($i = 3; $i < count($data); $i++) {
                $row = $data[$i];
                
                // Pula linhas vazias ou cabeçalho
                if (empty(array_filter($row))) {
                    continue;
                }

                // Extrai os dados relevantes para cada contrato
                $contract = [
                    $row[self::IDX_CNPJ_LOTEADORA] ?? '', // CNPJ da loteadora
                    $header[1], // Ano calendário
                    $row[self::IDX_CPF_COMPRADOR] ?? '', // CPF/CNPJ do comprador
                    $row[self::IDX_NOME_COMPRADOR] ?? '', // Nome do comprador
                    $this->generateContractNumber($row), // Número do contrato
                    $row[self::IDX_DATA_VENDA] ?? '', // Data da venda
                    $row[self::IDX_VALOR_VENDA] ?? '0', // Valor da venda
                    $row[self::IDX_TIPO_OPERACAO] ?? '' // Tipo de operação
                ];

                $contracts[] = $contract;
            }

            return array_merge([$header], $contracts);

        } catch (\Exception $e) {
            throw new FileProcessingException('Erro ao ler arquivo: ' . $e->getMessage());
        }
    }

    private function generateContractNumber($row)
    {
        $parts = [
            $row[self::IDX_TIPO_OPERACAO] ?? '', // Tipo de operação
            $row[6] ?? '', // Número
            $row[7] ?? '', // Complemento
            $row[8] ?? ''  // CEP
        ];

        // Remove espaços e caracteres especiais
        $parts = array_map(function($part) {
            return preg_replace('/[^a-zA-Z0-9]/', '', $part);
        }, $parts);

        // Concatena as partes e limita a 20 caracteres
        return substr(implode('', $parts), 0, 20);
    }
}
