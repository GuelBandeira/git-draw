# GitHub Contribution Message Generator

Sistema PHP para gerar mensagens visuais no gráfico de contribuições do GitHub através de commits programáticos.

## 🚀 Funcionalidades

- ✅ Preview em tempo real do grid 52 semanas × 7 dias
- ✅ Suporte para letras, números e espaços
- ✅ Integração com GitHub OAuth
- ✅ Geração automática de commits
- ✅ Interface moderna com suporte a dark mode

## 📋 Pré-requisitos

- PHP 7.4 ou superior
- Servidor web (Apache/Nginx) ou WAMP/XAMPP
- Conta no GitHub
- Aplicativo OAuth do GitHub configurado

## ⚙️ Configuração

### 1. Criar Aplicativo OAuth no GitHub

1. Acesse: https://github.com/settings/developers
2. Clique em "New OAuth App"
3. Preencha os dados:
   - **Application name**: GitHub Contribution Generator
   - **Homepage URL**: `http://localhost/git-drawn`
   - **Authorization callback URL**: `http://localhost/git-drawn/callback.php`
4. Anote o **Client ID** e **Client Secret**

### 2. Configurar Credenciais

Edite o arquivo `config.php` e substitua:

```php
define('GITHUB_CLIENT_ID', 'YOUR_CLIENT_ID');
define('GITHUB_CLIENT_SECRET', 'YOUR_CLIENT_SECRET');
define('GITHUB_REDIRECT_URI', 'http://localhost/git-drawn/callback.php');
```

**Importante**: 
- Se estiver usando um domínio diferente, atualize a URL de redirecionamento no arquivo `config.php`
- O arquivo `config.php` está no `.gitignore` para proteger suas credenciais

### 3. Criar Repositório no GitHub

1. Crie um novo repositório no GitHub (pode ser privado)
2. Anote o nome no formato: `usuario/repositorio`

## 🎯 Como Usar

1. **Acesse a aplicação**: `http://localhost/git-drawn`

2. **Digite sua mensagem**: 
   - Máximo de 10 caracteres
   - Suporta letras (A-Z), números (0-9) e espaços
   - Exemplo: "2024", "HELLO", "TEST"

3. **Visualize o preview**: 
   - O grid será atualizado automaticamente
   - Veja quantos commits serão necessários

4. **Conecte-se ao GitHub**:
   - Clique em "Conectar com GitHub"
   - Autorize a aplicação
   - Você será redirecionado de volta

5. **Gere os commits**:
   - Informe o nome do repositório (ex: `seu-usuario/git-drawn`)
   - Clique em "Gerar Commits no GitHub"
   - Aguarde o processo (pode levar alguns minutos)

## 📝 Notas Importantes

### Sobre os Commits

- Os commits são criados com datas retroativas (52 semanas atrás até hoje)
- O GitHub pode levar algumas horas para atualizar o gráfico de contribuições
- O gráfico mostra contribuições do último ano, então commits muito antigos podem não aparecer
- Para melhor resultado, use um repositório novo ou pouco usado

### Limitações

- **Rate Limiting**: O GitHub limita a 5.000 requisições por hora para usuários autenticados
- **Tempo**: Gerar muitos commits pode levar vários minutos
- **Datas**: Commits com datas muito antigas podem não aparecer no gráfico

### Caracteres Suportados

- Números: 0-9
- Letras: A-Z (maiúsculas e minúsculas são tratadas como maiúsculas)
- Espaço: para separar palavras

## 🔧 Estrutura de Arquivos

```
git-drawn/
├── index.php              # Interface principal
├── auth.php               # Inicia autenticação OAuth
├── callback.php           # Processa callback do OAuth
├── logout.php             # Faz logout
├── generate_commits.php   # Gera commits via API
├── css/
│   └── style.css          # Estilos
├── js/
│   └── script.js          # JavaScript
└── README.md              # Este arquivo
```

## 🐛 Solução de Problemas

### Erro: "Não autenticado"
- Verifique se você completou o fluxo OAuth
- Tente desconectar e conectar novamente

### Erro: "Failed to create commit"
- Verifique se o repositório existe
- Verifique se você tem permissão de escrita no repositório
- Verifique se o branch é `main` ou `master`

### Commits não aparecem no gráfico
- Aguarde algumas horas (o GitHub atualiza periodicamente)
- Verifique se os commits foram criados no repositório correto
- Verifique se as datas dos commits estão dentro do último ano

### Rate Limit Exceeded
- Aguarde 1 hora antes de tentar novamente
- Reduza o número de commits (use mensagens menores)

## 📄 Licença

Este projeto é fornecido como está, sem garantias.

## 🤝 Contribuições

Sinta-se livre para melhorar este projeto!

