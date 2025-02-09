<div align="center">
    <img src="docs/assets/jcbytes_logo.png" alt="JC Bytes Logo" width="200"/>
    <h3>JC Bytes - Soluções em Tecnologia</h3>
    <p>Transformando dados em conformidade fiscal</p>
</div>

# Manual do Sistema DIMOB API

## Índice
1. [Visão Geral](#visão-geral)
2. [Arquitetura do Sistema](#arquitetura-do-sistema)
3. [Componentes Principais](#componentes-principais)
4. [Fluxo de Funcionamento](#fluxo-de-funcionamento)
5. [Guia de Instalação](#guia-de-instalação)
6. [Guia de Integração](#guia-de-integração)
7. [Exemplos de Uso](#exemplos-de-uso)
8. [Troubleshooting](#troubleshooting)

## Visão Geral

A DIMOB API é um sistema desenvolvido pela JC Bytes para automatizar a geração de arquivos DIMOB (Declaração de Informações sobre Atividades Imobiliárias) no formato exigido pela Receita Federal. O sistema foi projetado para ser facilmente integrável com sistemas existentes e suporta múltiplos formatos de entrada (CSV, XLS E XLSX).

### Principais Funcionalidades
- Conversão de arquivos CSV/XLSX para o formato DIMOB
- Validação automática dos dados de entrada
- Formatação precisa conforme especificações da Receita Federal
- API RESTful para integração com outros sistemas
- Interface web para uploads manuais
- Suporte a múltiplos tipos de operações imobiliárias

## Arquitetura do Sistema

### Tecnologias Utilizadas
- PHP 8.0+
- Framework Slim 4.0
- MySQL/MariaDB
- Composer para gerenciamento de dependências
- PHPSpreadsheet para leitura de arquivos Excel

### Estrutura de Diretórios
```
DIMOB_API/
├── src/
│   ├── Controllers/
│   │   └── DimobController.php
│   ├── Services/
│   │   ├── FileReader.php
│   │   └── DimobFormatter.php
│   └── Models/
├── public/
│   └── index.php
├── config/
│   └── settings.php
├── templates/
│   └── upload.html
└── vendor/
```

## Componentes Principais

### 1. FileReader (src/Services/FileReader.php)
Responsável pela leitura e interpretação dos arquivos de entrada.

#### Funcionalidades
- Detecção automática do formato do arquivo
- Extração de dados do cabeçalho (CNPJ, Ano, Razão Social)
- Validação dos dados de entrada
- Normalização dos dados para processamento

### 2. DimobFormatter (src/Services/DimobFormatter.php)
Responsável pela formatação dos dados no padrão DIMOB.

#### Funcionalidades
- Geração do registro R01 (dados da empresa)
- Geração do registro R02 (ano calendário)
- Geração dos registros IR (informações dos imóveis)
- Formatação de valores monetários
- Tratamento de caracteres especiais

### 3. DimobController (src/Controllers/DimobController.php)
Gerencia as requisições HTTP e coordena o processo de conversão.

#### Endpoints
- POST /api/convert - Conversão via API
- GET /upload - Interface web para upload
- POST /upload - Processamento do upload via web

## Fluxo de Funcionamento

1. **Recebimento dos Dados**
   - Via API REST (POST /api/convert)
   - Via interface web (upload de arquivo)

2. **Validação Inicial**
   - Verificação do formato do arquivo
   - Validação do tamanho e tipo do arquivo
   - Verificação dos campos obrigatórios

3. **Processamento dos Dados**
   - Leitura do arquivo pelo FileReader
   - Extração dos dados do cabeçalho
   - Processamento das linhas de dados

4. **Formatação DIMOB**
   - Geração do registro R01
   - Geração do registro R02
   - Geração dos registros IR
   - Aplicação das regras de formatação

5. **Entrega do Resultado**
   - Geração do arquivo .txt
   - Retorno do arquivo via API ou download

## Guia de Instalação

### Pré-requisitos
- PHP 8.0 ou superior
- Composer
- MySQL/MariaDB
- Extensões PHP: php-xml, php-mysql, php-mbstring

### Passos de Instalação

1. **Clone o repositório**
```bash
git clone https://github.com/seu-usuario/dimob-api.git
cd dimob-api
```

2. **Instale as dependências**
```bash
composer install
```

3. **Configure o ambiente**
```bash
cp .env.example .env
# Edite o arquivo .env com suas configurações
```

4. **Configure o banco de dados**
```bash
# Execute o script SQL de criação do banco
mysql -u seu_usuario -p < database/schema.sql
```

5. **Configure o servidor web**
```apache
# Exemplo de configuração Apache
<VirtualHost *:80>
    ServerName dimob-api.local
    DocumentRoot /path/to/dimob-api/public
    <Directory /path/to/dimob-api/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

## Guia de Integração

### 1. Integração via API REST

#### Endpoint de Conversão
```http
POST /api/convert
Content-Type: multipart/form-data

file: [arquivo.csv ou arquivo.xlsx]
```

#### Exemplo de Resposta
```json
{
  "success": true,
  "file": "DIMOB_20250102051108.txt",
  "download_url": "/download/DIMOB_20250102051108.txt"
}
```

### 2. Integração via Biblioteca PHP

```php
use App\Services\FileReader;
use App\Services\DimobFormatter;

// Inicializa os serviços
$reader = new FileReader();
$formatter = new DimobFormatter();

// Processa o arquivo
$data = $reader->read($filepath);
$dimobContent = $formatter->format($data);

// Salva o arquivo DIMOB
file_put_contents('DIMOB_' . date('YmdHis') . '.txt', $dimobContent);
```

### 3. Integração com Sistemas Existentes

#### Via Banco de Dados
1. Crie uma view no seu sistema que formate os dados no padrão esperado
2. Configure a conexão no arquivo .env
3. Utilize a API para buscar os dados diretamente do banco

#### Via Exportação de Dados
1. Adicione um botão de "Exportar para DIMOB" no seu sistema
2. Gere um arquivo CSV/XLSX no formato esperado
3. Envie o arquivo para a API de conversão

## Exemplos de Uso

### 1. Conversão via cURL
```bash
curl -X POST \
  -F "file=@dados.csv" \
  http://seu-servidor/api/convert
```

### 2. Conversão via PHP
```php
$ch = curl_init('http://seu-servidor/api/convert');
$data = array('file' => new CURLFile('dados.csv'));
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
$response = curl_exec($ch);
```

### 3. Integração com Sistema Laravel
```php
// No seu controller
public function exportToDimob()
{
    $data = YourModel::formatForDimob()->get();
    $csv = $this->generateCsv($data);
    
    $client = new Client();
    $response = $client->post('http://seu-servidor/api/convert', [
        'multipart' => [
            [
                'name' => 'file',
                'contents' => fopen($csv, 'r'),
                'filename' => 'export.csv'
            ]
        ]
    ]);
    
    return response()->json(json_decode($response->getBody()));
}
```

## Troubleshooting

### Problemas Comuns e Soluções

1. **Erro: "Formato de arquivo inválido"**
   - Verifique se o arquivo está no formato CSV ou XLSX
   - Verifique a codificação do arquivo (deve ser UTF-8)
   - Verifique se todos os campos obrigatórios estão presentes

2. **Erro: "CNPJ inválido"**
   - Verifique se o CNPJ está no formato correto
   - Verifique se o CNPJ é válido
   - Verifique se o CNPJ está cadastrado na base

3. **Erro: "Valor inválido"**
   - Verifique se os valores monetários estão no formato correto
   - Verifique se não há caracteres especiais nos valores
   - Verifique se os valores negativos estão corretamente sinalizados

### Logs e Depuração
- Os logs são armazenados em `logs/app.log`
- Ative o modo debug no .env para mais informações
- Use o Postman ou similar para testar a API

### Suporte
- Abra uma issue no GitHub para reportar problemas
- Consulte a documentação oficial da Receita Federal para dúvidas sobre o formato DIMOB
- Entre em contato com o suporte técnico para questões específicas
- Email: jhon.freire@ftc.edu.br
- WhatsApp: (73) 9 8172-3483

## Licença

Copyright 2024 JC Bytes - Soluções em Tecnologia  
Todos os direitos reservados
