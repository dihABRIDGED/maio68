# Sistema Educacional - TCC

Sistema completo de gestão educacional com design moderno e funcionalidades profissionais.

## 🚀 Funcionalidades

### 👥 **Para Todos os Usuários:**
- **Login** moderno e seguro
- **Dashboard** com métricas personalizadas
- **Cronograma** de aulas interativo
- **Suporte** com informações de contato

### 👨‍🎓 **Aluno:**
- Visualizar faltas e frequência
- Gráficos de desempenho
- Cronograma de aulas
- Contatos de professores

### 👨‍🏫 **Professor:**
- Fazer chamada
- Agendar aulas
- Ver faltas dos alunos
- Cronograma completo

### 👨‍💼 **Coordenador:**
- Gerenciar usuários (CRUD)
- Gerenciar disciplinas (CRUD)
- Editar cronograma
- Gerenciar contatos de suporte
- Relatórios e estatísticas

## 📦 Instalação

### 1. **Banco de Dados:**
```sql
-- 1. Importar estrutura base
mysql -u root -p < 127_0_0_1(7).sql

-- 2. Adicionar dados realistas
mysql -u root -p < dados_realistas.sql

-- 3. Atualizar cronograma
mysql -u root -p < cronograma_upgrade.sql

-- 4. Adicionar suporte
mysql -u root -p < suporte_contatos.sql
```

### 2. **Configuração:**
- Editar `includes/connection.php` com suas credenciais do banco
- Configurar servidor web (Apache/Nginx) apontando para a pasta
- Certificar que PHP está habilitado

### 3. **Credenciais de Teste:**
- **Aluno:** `rodrigo.silva@estudante.fluxus.edu` / `123456`
- **Professor:** `carla.ribeiro@fluxus.edu` / `123456`
- **Coordenador:** `ana.souza@fluxus.edu` / `123456`

## 📁 Estrutura

```
sistema-educacional/
├── login.php              # Página de login
├── css/
│   └── modern.css         # Estilos modernos
├── images/
│   ├── logo.png          # Logo do sistema
│   └── lamp.png          # Ícone
├── includes/
│   ├── connection.php    # Conexão com BD
│   ├── auth.php         # Autenticação
│   ├── header.php       # Navegação
│   └── logout.php       # Logout
├── pages/
│   ├── home.php         # Dashboard
│   ├── faltas.php       # Controle de faltas
│   ├── cronograma.php   # Cronograma de aulas
│   ├── usuarios.php     # Gerenciar usuários
│   ├── disciplinas.php  # Gerenciar disciplinas
│   ├── suporte.php      # Central de suporte
│   ├── suporte_ajax.php # AJAX do suporte
│   ├── chamada.php      # Fazer chamada
│   └── agendamento.php  # Agendar aulas
└── *.sql               # Scripts do banco
```

## 🎨 Design

- **Cores:** Cinza, branco, preto e vermelho
- **Estilo:** Corporativo moderno
- **Responsivo:** Mobile-first
- **Componentes:** Cards, gráficos, tabelas, modais
- **Animações:** Suaves e profissionais

## 🔧 Tecnologias

- **Frontend:** HTML5, CSS3, JavaScript
- **Backend:** PHP 7.4+
- **Banco:** MySQL 5.7+
- **Gráficos:** Chart.js
- **Ícones:** Font Awesome
- **Design:** CSS Grid, Flexbox

## 📊 Banco de Dados

### Tabelas Principais:
- `usuario` - Usuários do sistema
- `disciplina` - Disciplinas
- `aula` - Cronograma de aulas
- `matricula` - Matrículas dos alunos
- `frequencia` - Controle de presença
- `contato_suporte` - Informações de contato

## 🎓 Desenvolvido para TCC

Sistema completo e profissional desenvolvido especificamente para Trabalho de Conclusão de Curso, com foco em:

- **Qualidade de código**
- **Design moderno**
- **Funcionalidades completas**
- **Integração com banco de dados**
- **Responsividade**
- **Experiência do usuário**

---

**Desenvolvido com ❤️ para seu TCC**
