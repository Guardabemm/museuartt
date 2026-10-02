# MuseuArt — Sistema de Gestão de Museu e Exposições

O **MuseuArt** é uma aplicação web desenvolvida para a gestão integrada de acervos museológicos, acompanhamento de funcionários, monitorização de restaurações e organização de exposições temporárias por alas.

---

## Funcionalidades Principais

### Dashboard e Painel Geral
- **Resumo do Acervo:** Indicadores em tempo real do total de itens, número de funcionários cadastrados, exposições e obras em restauração.
- **Gráfico Interativo de Categorias:** Distribuição percentual do acervo por tipo (Quadros, Esculturas, Pinturas, Taxidermia, Fósseis, etc.).
- **Status Operacional dos Itens:** Acompanhamento rápido das obras (Em Exposição, Em Restauração, Reserva Técnica e Indisponíveis).
- **Atividade Recente:** Feed com o histórico das últimas ações executadas no sistema pelos utilizadores.
- **Itens por Ala:** Visão rápida da alocação das obras nas diferentes alas do museu.

### Gestão de Exposições Temporárias
- **Calendário Dinâmico:** Visualização interativa dos dias com exposições programadas ou ativas.
- **Painéis por Status:** Separação automática das exposições em *Em andamento*, *Programadas* e *Encerradas (Histórico)*.
- **Validação Temporal Estrita:** Regra de negócio que impede o agendamento/início de exposições no próprio dia ou em datas passadas (permitido apenas a partir do dia seguinte).
- **Modal de Detalhes:** Visualização completa da exposição com foto de capa, autor/responsável, horário e descrição detalhada.

---

## Tecnologias Utilizadas

- **Front-end:** 
  - HTML5 & CSS3 (Grid Layout responsivo e variáveis)
  - JavaScript (ES6+ assíncrono e manipulação do DOM)
  - [Bootstrap Icons](https://icons.getbootstrap.com/)
  - [Chart.js](https://www.chartjs.org/) (Gráficos estatísticos)
- **Back-end:**
  - PHP 8.x (Arquitetura estruturada e validação de regras de negócio)
- **Banco de Dados:**
  - MySQL / MariaDB (Driver `mysqli` com *Prepared Statements* para segurança contra SQL Injection)

---

## Estrutura de Diretórios

```text
museuart/
├── assets/
│   ├── css/            # Folhas de estilo da aplicação e páginas específicas
│   ├── js/             # Scripts (dashboard.js, exposicoes.js, etc.)
│   └── img/            # Uploads de obras, exposições e funcionários
├── backend/
│   └── Config/
│       └── conexao.php # Ficheiro de conexão à base de dados MySQL
├── components/
│   └── atividade_recente.php
└── sistem/
    └── administrador/
        ├── dashboard/  # Vista principal do dashboard
        └── exposicao/  # Vista e processamento da gestão de exposições
```
### Instalação e Configuração

## Requisitos Previstos:
Servidor web local (XAMPP, WAMP, Laragon ou PHP CLI)
PHP 8.0 ou superior
MySQL / MariaDB

## Passos:
1. **Clonar o Repositório:**
Bash
git clone [https://github.com/teu-usuario/museuart.git](https://github.com/teu-usuario/museuart.git)

2. **Configurar a Base de Dados:**
Importa a estrutura de tabelas necessária (item, alocacao, registro, funcionario, exposicao).

3. **Configura as credenciais de acesso no ficheiro backend/Config/conexao.php:**
PHP
$strcon = mysqli_connect('localhost', 'usuario', 'senha', 'museuart');

4. **Executar a Aplicação:**
Copia a pasta do projeto para a diretoria do teu servidor local (ex: htdocs no XAMPP).

5. **Acede no navegador através de:** http://localhost/SistemaMuseuArt.GuardaBem/public

## Regras de Negócio Importantes

**Início de Exposições:** Por regra de negócio, uma exposição não pode ser cadastrada para iniciar no próprio dia nem em datas passadas.

**Cálculo Automático de Status:** O status de uma exposição (Programada, Em andamento, Encerrada) é recalculado e atualizado dinamicamente com base no fuso horário local (America/Sao_Paulo).

## Licença
Este projeto foi desenvolvido para fins acadêmicos e de pesquisa.
