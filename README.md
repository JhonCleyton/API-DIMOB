# DIMOB API

<div align="center">
    <img src="docs/assets/jcbytes_logo.png" alt="JC Bytes Logo" width="200"/>
    <h3>JC Bytes - Soluções em Tecnologia</h3>
    <p>Transformando dados em conformidade fiscal</p>
</div>

## Sobre o Projeto

A DIMOB API é uma solução desenvolvida pela JC Bytes para automatizar a geração de arquivos DIMOB (Declaração de Informações sobre Atividades Imobiliárias) no formato exigido pela Receita Federal.

### Características

✨ Interface amigável para upload de arquivos  
🚀 Processamento rápido e eficiente  
📊 Suporte a múltiplos formatos (CSV, XLSX)  
🔒 Validação rigorosa dos dados  
🔄 Fácil integração com sistemas existentes

## Índice

1. [Visão Geral](#visão-geral)
2. [Requisitos do Sistema](#requisitos-do-sistema)
3. [Instalação](#instalação)
4. [Configuração](#configuração)
5. [Uso](#uso)
6. [Estrutura de Arquivos](#estrutura-de-arquivos)
7. [Formato dos Arquivos](#formato-dos-arquivos)
8. [API Endpoints](#api-endpoints)
9. [Solução de Problemas](#solução-de-problemas)

## Visão Geral

O sistema oferece uma interface web amigável para converter arquivos CSV, XLS e XLSX para o formato DIMOB exigido pela Receita Federal. O sistema:
- Aceita upload de arquivos via interface drag-and-drop
- Valida o formato dos arquivos
- Extrai informações da empresa
- Gera registros R01 (dados da empresa), R02 (ano calendário) e IR (contratos)
- Permite download do arquivo convertido

## Requisitos do Sistema

- Windows 7 ou superior
- 4GB RAM (mínimo)
- 1GB de espaço em disco
- Conexão com internet (para download dos instaladores)

## Instalação

### 1. Instalação do XAMPP

1. Baixe o XAMPP com PHP 8.0 ou superior em: https://www.apachefriends.org/download.html
2. Execute o instalador
3. Durante a instalação, selecione os componentes:
   - Apache
   - PHP
   - MySQL
4. Mantenha o diretório padrão (`C:\xampp`)

### 2. Instalação do Composer

1. Baixe o Composer em: https://getcomposer.org/Composer-Setup.exe
2. Execute o instalador
3. Certifique-se de que o instalador detecte corretamente o PHP do XAMPP

### 3. Configuração do Projeto

1. Baixe o código do projeto
2. Extraia para `C:\Users\[SEU_USUARIO]\OneDrive\Documentos\DIMOB_API`
3. Abra o terminal como administrador
4. Navegue até o diretório do projeto:
   ```bash
   cd C:\Users\[SEU_USUARIO]\OneDrive\Documentos\DIMOB_API
   ```
5. Instale as dependências:
   ```bash
   composer install
   ```

## Configuração

### 1. Configuração do Apache

1. Abra `C:\xampp\apache\conf\httpd.conf`
2. Adicione no final do arquivo:
   ```apache
   Alias /dimob_api "C:/Users/[SEU_USUARIO]/OneDrive/Documentos/DIMOB_API/public"
   <Directory "C:/Users/[SEU_USUARIO]/OneDrive/Documentos/DIMOB_API/public">
       Options Indexes FollowSymLinks
       AllowOverride All
       Require all granted
       
       <IfModule mod_rewrite.c>
           RewriteEngine On
           RewriteBase /dimob_api/
           RewriteCond %{REQUEST_FILENAME} !-f
           RewriteCond %{REQUEST_FILENAME} !-d
           RewriteRule ^ index.php [QSA,L]
       </IfModule>

       <FilesMatch \.php$>
           SetHandler application/x-httpd-php
       </FilesMatch>
   </Directory>
   ```

### 2. Configuração do Ambiente

1. Copie o arquivo `.env.example` para `.env`:
   ```bash
   copy .env.example .env
   ```
2. Configure as variáveis no `.env`:
   ```env
   DB_HOST=localhost
   DB_NAME=dimob_api
   DB_USER=root
   DB_PASS=
   UPLOAD_DIR=C:/Users/[SEU_USUARIO]/OneDrive/Documentos/DIMOB_API/uploads/
   OUTPUT_DIR=C:/Users/[SEU_USUARIO]/OneDrive/Documentos/DIMOB_API/output/
   ```

### 3. Criação dos Diretórios

1. Crie os diretórios necessários:
   ```bash
   mkdir uploads
   mkdir output
   ```

### 4. Permissões

1. Dê permissão de escrita aos diretórios:
   - Clique com botão direito em `uploads` e `output`
   - Propriedades → Segurança → Editar
   - Adicione permissão total para o usuário SYSTEM

## Uso

1. Inicie o XAMPP Control Panel
2. Inicie o Apache
3. Acesse: http://localhost/dimob_api/
4. Na interface web:
   - Arraste um arquivo ou clique para selecionar
   - O arquivo deve ser CSV, XLS ou XLSX
   - Aguarde o processamento
   - Faça o download do arquivo DIMOB gerado

## Estrutura de Arquivos

```
DIMOB_API/
├── public/              # Arquivos públicos
│   ├── index.php       # Ponto de entrada
│   ├── index.html      # Interface web
│   └── .htaccess       # Configuração do Apache
├── src/                # Código fonte
│   ├── Controllers/    # Controladores
│   └── Services/       # Serviços
├── uploads/            # Diretório de upload
├── output/             # Arquivos convertidos
├── vendor/             # Dependências
├── .env               # Configurações
└── composer.json      # Dependências do projeto
```

## Formato dos Arquivos

### Arquivo de Entrada (CSV/XLS/XLSX)

O arquivo deve conter duas partes:

1. Cabeçalho (primeira linha):
   ```
   CNPJ,ANO,RAZAO_SOCIAL,NOME_FANTASIA
   12345678901234,2023,EMPRESA EXEMPLO LTDA,EMPRESA EXEMPLO
   ```

2. Dados dos contratos:
   ```
   CNPJ,ANO,CPF_CNPJ,NOME,CONTRATO,DATA,VALOR
   12345678901234,2023,12345678901,JOAO DA SILVA,CONTRATO001,01/01/2023,1000.00
   ```

### Arquivo de Saída (DIMOB)

O sistema gera três tipos de registros:

1. R01 - Dados da empresa:
   ```
   R01[CNPJ14][NOME60][COMPLEMENTO23]
   ```

2. R02 - Ano calendário:
   ```
   R02[ANO4][COMPLEMENTO93]
   ```

3. IR - Dados dos contratos:
   ```
   IR[CPF_CNPJ14][NOME40][CONTRATO15][DATA8][VALOR15][COMPLEMENTO5]
   ```

## API Endpoints

- `POST /api/convert` - Conversão de arquivos
- `GET /api/upload` - Interface de upload
- `GET /output/{filename}` - Download de arquivos
- `GET /status` - Status da API

## Solução de Problemas

### Erro 404
- Verifique se o Apache está rodando
- Confirme se o arquivo .htaccess está presente
- Verifique se o mod_rewrite está habilitado

### Erro 500
- Verifique os logs do Apache em `C:\xampp\apache\logs`
- Confirme as permissões dos diretórios
- Verifique se todas as extensões PHP estão habilitadas

### Erro de Upload
- Verifique o tamanho máximo permitido no php.ini
- Confirme se o diretório de upload tem permissões corretas
- Verifique o formato do arquivo

### Problemas Comuns
- **Arquivo não processa**: Verifique a formatação do CSV/XLSX
- **Erro de memória**: Aumente o limite no php.ini
- **Caracteres incorretos**: Use codificação UTF-8

## Suporte

### Canais de Atendimento
- Email: suporte@jcbytes.com.br
- WhatsApp: (11) 99999-9999
- Horário: Segunda a Sexta, 9h às 18h

### Documentação Adicional
- [Manual Completo](docs/MANUAL_DIMOB_API.md)
- [Especificação DIMOB](docs/ESPECIFICACAO_DIMOB.pdf)
- [FAQ](docs/FAQ.md)

## Licença

Este projeto está licenciado sob a [Licença MIT](LICENSE) - veja o arquivo LICENSE para detalhes.

## Agradecimentos

- Equipe JC Bytes
- Contribuidores
- Nossos clientes pelo feedback valioso
