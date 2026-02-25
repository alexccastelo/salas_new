# Sugestões de Melhorias: Clínica Salas

Com base na análise da estrutura atual do projeto, foram identificadas várias oportunidades de melhoria focadas em **manutenibilidade, segurança, escalabilidade e qualidade de código**. 

Como o sistema já demonstra estar em um processo de transição para uma arquitetura mais organizada (MVC), as sugestões abaixo visam concluir essa transição e modernizar a aplicação:

## 1. Arquitetura e Organização de Código (Refatoração)

*   **Concluir a Migração para MVC:** O sistema possui duas abordagens convivendo atualmente (arquivos legados tipo `relatorio.php` na raiz e a nova pasta `src/Controllers`). O ideal é migrar todo o código dos arquivos `.php` da raiz para dentro da nova estrutura `src/` (Controllers, Models e Views), deletando os arquivos legados (`_legacy.php`, `_old.php`, etc.) para evitar confusão de manutenção e duplicidade de regras de negócio.
*   **Melhorar o Sistema de Rotas:** O arquivo `index.php` atual usa um bloco `switch` simples para instanciar as rotas. Para um sistema escalável, seria interessante implementar um roteador mais robusto (como o `bramus/router` ou `nikic/fast-route`) que suporte verbos HTTP explícitos (GET, POST, PUT, DELETE) e rotas amigáveis de forma mais limpa.
*   **Remover Lógica do `config.php`:** O arquivo `config.php` está acumulando muitas responsabilidades. Atualmente, ele conecta no banco, cria as tabelas (`CREATE TABLE IF NOT EXISTS`), insere o usuário padrão e ainda contém funções auxiliares e regras de permissão. 
    *   **Sugestão:** Separar a criação de tabelas em um sistema de **Migrações** (scripts isolados rodados apenas quando o banco de dados precisa ser atualizado, ex: Phinx).
    *   Mover as funções auxiliares para classes estáticas específicas dentro de `src/Helpers/`.
*   **Evitar o uso de Globais (`global $pdo`):** Nos arquivos mais antigos e no controle de sessão, estão sendo usadas variáveis globais. O fluxo ideal na arquitetura MVC é utilizar a **Injeção de Dependência** ou o padrão Singleton garantido, passando a conexão do banco de dados (PDO) via construtor para os Models, o que facilita manutenibilidade e a escrita de testes no futuro.

## 2. Segurança e Configurações

*   **Uso de Variáveis de Ambiente (`.env`):** Dados sensíveis, como a senha do administrador padrão, o fuso horário (timezone) e o caminho do arquivo do banco de dados, estão fixos (hardcoded) no código-fonte. Incorporar uma biblioteca como `vlucas/phpdotenv` permite que você isole essas informações sensíveis em um arquivo `.env` (que não será versionado no repositório Github), blindando vazamentos críticos.
*   **Proteção CSRF (Cross-Site Request Forgery):** A aplicação manipula informações financeiras e de cadastro, mas não possui proteção contra falsificação de solicitações entre sites (CSRF) nos seus formulários. É altamente recomendável gerar e validar *tokens* CSRF aleatórios em todas as requisições que alteram dados no servidor (métodos POST/PUT/DELETE).
*   **Evolução do Controle de Acesso (ACL):** Atualmente, as permissões verificam se um usuário tem acesso direto a um módulo individual de forma "binária". Para escalar, o ideal seria adotar o modelo **RBAC (Role-Based Access Control)**, definindo "Perfis ou Grupos" preestabelecidos (ex: Administrador, Recepcionista, Profissional Limitado) e atrelando esses grupos aos usuários.

## 3. Banco de Dados e Escalabilidade

*   **Substituição do DB SQLite:** O SQLite contido fisicamente na aplicação é excelente para protótipos e sistemas internos de baixa carga. No entanto, em um cenário de múltiplos acessos recorrentes concorrentes (várias recepcionistas e profissionais logados ao mesmo tempo salvando registros), ele pode sofrer com perdas de dados ou travamentos clássicos de escrita rápida (*"database is locked"*). Para assegurar uso em produção estável, planeje uma migração suave para **PostgreSQL** ou **MySQL/MariaDB**.
*   **Trilhas de Auditoria (Logs):** Especialmente em manipulações que envolvem os "Pacotes" e "Registros de Uso", é fundamental rastrear e registrar **quem** fez a alteração e **quando**. Criar uma tabela de logs de sistemas ajuda bastante a auditar exclusões indevidas e salvar registros retroativos em caso de discrepâncias financeiras de pacotes fechados.

## 4. Qualidade e Ferramentas Modernas

*   **Gerenciador de Dependências Oficial (Composer):** Se o ecossistema ainda não utiliza largamente as vantagens do Composer (`composer.json`), adotá-lo não apenas vai gerenciar de forma sofisticada e nativa o `autoloader.php` via standard PSR-4, mas também dará portas abertas para adicionar pacotes consolidados em necessidades de negócio futuras (como geração de boletos, PDFs avançados e disparos de e-mails em massa).
*   **Front-End e Template Engines:** Para as *Views*, procure evitar misturar lógicas difíceis ou cálculos diretos de PHP no meio das tags do HTML. Pode-se adotar uma *Template Engine* leve, como o **Twig**, para extrair o máximo de legibilidade e segurança (escapando strings implicitamente para prevenir ataques de XSS) da exibição para a tela final do usuário.
*   **Testes Automatizados:** Não existe garantia de que manutenções no código atual não quebrarão funções legadas existentes. Em momentos estratégicos, escrever testes unitários simples (ex: com `PHPUnit`), especialmente focados para funções de risco como de cálculo de tempo em horas decimais (`calcularHorasDecimais`) e fluxo de faturamento de pacotes, garantirá confiabilidade contra regressões.
