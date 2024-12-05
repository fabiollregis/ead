# EAD School - Plataforma de Ensino à Distância

Uma plataforma de ensino à distância (EAD) robusta e intuitiva, desenvolvida em PHP com MySQL, projetada para fornecer uma experiência educacional online eficiente e segura.

## 🚀 Funcionalidades

- Sistema completo de autenticação e autorização
- Gerenciamento de usuários (admin/usuário)
- Categorização de aulas
- Upload e gerenciamento de vídeos do YouTube
- Sistema de arquivos para materiais complementares
- Painel administrativo completo
- Relatórios e logs de acesso
- Interface personalizável
- Sistema de configurações do site

## 💻 Requisitos do Sistema

- PHP 7.4 ou superior
- MySQL 5.7 ou superior
- Servidor Web (Apache recomendado)
- Extensões PHP necessárias:
  - PDO
  - PDO_MySQL
  - session
  - fileinfo

## 🛠️ Instalação

1. Clone ou faça download do repositório
2. Configure seu servidor web para apontar para o diretório do projeto
3. Importe o arquivo `database.sql` para criar a estrutura do banco de dados
4. Configure as credenciais do banco de dados no arquivo `config.php`
5. Certifique-se que os diretórios `uploads/` e `logs/` têm permissões de escrita
6. Acesse o sistema através do navegador

## 📁 Estrutura do Projeto

```
ead-school/
├── assets/           # Arquivos estáticos (CSS, JS, imagens)
├── includes/         # Arquivos de inclusão PHP
├── logs/            # Logs do sistema
├── uploads/         # Arquivos enviados
├── config.php       # Configurações do sistema
├── database.sql     # Estrutura do banco de dados
└── [demais arquivos PHP] # Arquivos de funcionalidades
```

## 🔐 Segurança

O sistema implementa diversas medidas de segurança:
- Proteção contra SQL Injection usando PDO
- Proteção contra XSS
- Cookies seguros com httponly
- Controle de sessão
- Logs de acesso
- Validação de uploads
- Sanitização de arquivos

## 👥 Tipos de Usuário

1. **Administrador**
   - Gerenciamento completo do sistema
   - Acesso a todas as funcionalidades
   - Visualização de relatórios

2. **Usuário**
   - Acesso às aulas
   - Download de materiais
   - Gerenciamento de perfil

## 📊 Funcionalidades Principais

### Gerenciamento de Aulas
- Criação e edição de aulas
- Upload de materiais complementares
- Integração com YouTube
- Categorização de conteúdo

### Sistema de Categorias
- Organização hierárquica de conteúdo
- Filtros e busca
- Status ativo/inativo

### Relatórios
- Logs de acesso
- Estatísticas de visualização
- Downloads de materiais
- Atividades dos usuários

## 🎨 Personalização

O sistema permite personalização através do painel administrativo:
- Logo do site
- Nome do site
- Descrição
- Cores do cabeçalho
- Cores dos textos

## 📝 Licença

Este projeto está sob licença proprietária. Todos os direitos reservados.

## 🤝 Suporte

Para suporte ou dúvidas, entre em contato com o administrador do sistema.
