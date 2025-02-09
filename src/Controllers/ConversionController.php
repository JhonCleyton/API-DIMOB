<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\FileReader;
use App\Services\DimobFormatter;

class ConversionController
{
    private $fileReader;
    private $dimobFormatter;

    public function __construct()
    {
        $this->fileReader = new FileReader();
        $this->dimobFormatter = new DimobFormatter();
    }

    public function convert(Request $request, Response $response)
    {
        try {
            // Verifica se um arquivo foi enviado
            $uploadedFiles = $request->getUploadedFiles();
            if (empty($uploadedFiles['file'])) {
                throw new \Exception('Nenhum arquivo foi enviado');
            }

            $uploadedFile = $uploadedFiles['file'];
            if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
                throw new \Exception('Erro no upload do arquivo: ' . $uploadedFile->getError());
            }

            // Verifica a extensão do arquivo
            $extension = strtolower(pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION));
            if (!in_array($extension, ['csv', 'xls', 'xlsx'])) {
                throw new \Exception('Formato de arquivo não suportado. Use CSV, XLS ou XLSX.');
            }

            // Cria diretório de upload se não existir
            $uploadDir = $_ENV['UPLOAD_DIR'];
            if (!file_exists($uploadDir)) {
                if (!mkdir($uploadDir, 0777, true)) {
                    throw new \Exception('Não foi possível criar o diretório de upload');
                }
            }

            if (!is_writable($uploadDir)) {
                throw new \Exception('Diretório de upload não tem permissão de escrita');
            }

            // Move o arquivo enviado
            $filename = uniqid() . '.' . $extension;
            $filepath = $uploadDir . $filename;
            
            try {
                $uploadedFile->moveTo($filepath);
            } catch (\Exception $e) {
                throw new \Exception('Erro ao mover arquivo: ' . $e->getMessage());
            }

            if (!file_exists($filepath)) {
                throw new \Exception('Arquivo não foi movido corretamente');
            }

            // Lê o arquivo
            try {
                $data = $this->fileReader->read($filepath);
            } catch (\Exception $e) {
                throw new \Exception('Erro ao ler arquivo: ' . $e->getMessage());
            }

            // Formata os dados para DIMOB
            try {
                $dimobContent = $this->dimobFormatter->format($data);
            } catch (\Exception $e) {
                throw new \Exception('Erro ao formatar dados: ' . $e->getMessage());
            }

            // Cria diretório de saída se não existir
            $outputDir = $_ENV['OUTPUT_DIR'];
            if (!file_exists($outputDir)) {
                if (!mkdir($outputDir, 0777, true)) {
                    throw new \Exception('Não foi possível criar o diretório de saída');
                }
            }

            if (!is_writable($outputDir)) {
                throw new \Exception('Diretório de saída não tem permissão de escrita');
            }

            // Salva o arquivo DIMOB
            $outputFile = $outputDir . 'DIMOB_' . date('YmdHis') . '.txt';
            if (file_put_contents($outputFile, $dimobContent) === false) {
                throw new \Exception('Erro ao salvar arquivo DIMOB');
            }

            // Remove o arquivo temporário
            if (file_exists($filepath)) {
                unlink($filepath);
            }

            // Retorna sucesso
            $result = [
                'success' => true,
                'message' => 'Arquivo convertido com sucesso',
                'file' => basename($outputFile)
            ];

            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            // Limpa arquivos temporários em caso de erro
            if (isset($filepath) && file_exists($filepath)) {
                unlink($filepath);
            }

            $result = [
                'success' => false,
                'error' => $e->getMessage()
            ];

            $response->getBody()->write(json_encode($result));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }
}
