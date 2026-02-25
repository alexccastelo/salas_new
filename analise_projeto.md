# Raio-X da Aplicação: Clínica Salas

Com base na análise do diretório e dos arquivos do projeto local (`/Users/alexccastelo/projetos/salas_new`), apresentamos abaixo a arquitetura, as tecnologias e as regras de negócio da aplicação.

## 1. Linguagem e Arquitetura
A aplicação é construída majoritariamente em **PHP**. 
Nota-se que ela está passando por uma transição formal em sua arquitetura:

- **Arquitetura Mista / Migração:** Ela contém rotinas legadas utilizando a abordagem procedural (onde o HTML e o PHP ficam misturados em arquivos na pasta raiz, como `config.php`, arquivos com o sufixo `_legacy.php`, etc.). No entanto, o arquivo principal de roteamento (`index.php`) e a estrutura de pastas introduzem um padrão **MVC (Model-View-Controller)** dentro da pasta `src/` (Namespaces `Clinica\Controllers`, `Models`, `Views`, `Services`, e `Helpers`), juntamente com o carregamento automático de classes via `autoloader.php`.
- **Frontend:** O design visual é formatado por meio de um arquivo `style.css` na raiz e exibições injetadas pelo PHP (Views/Templates HTML).

## 2. Banco de Dados
A aplicação utiliza o **SQLite** como sistema de gerenciamento de banco de dados relacional. 
O arquivo do banco é criado e armazenado localmente como `clinica_salas.db`. Toda a mecânica de conexão e as instruções de criação inicial das tabelas (`CREATE TABLE IF NOT EXISTS`) estão centralizadas no arquivo `config.php`, que faz uso da extensão `PDO` do PHP para a manipulação padronizada e segura dos dados.

## 3. Módulos e Regras de Negócio

A partir da estrutura de rotas e das entidades do banco de dados, os módulos existentes e suas principais lógicas funcionais são:

### Módulo de Check-in e Registros de Salas
- **Responsabilidade:** Registrar e cronometrar a estadia dos profissionais nas salas de atendimento.
- **Regras:** Quando um profissional ocupa uma sala, o sistema grava a data, a `hora_checkin` e a `hora_checkout`. Ao finalizar, calcula matematicamente as horas totais (em formato decimal) consumidas durante a ocupação daquele espaço. Este módulo também gera textos pré-formatados destinados ao WhatsApp. 

### Módulo de Pacotes de Horas/Sessões
- **Responsabilidade:** Gerenciar a contratação antecipada ou combinada de sessões (pacotes).
- **Regras:** Um pacote pode ser atrelado a um profissional ou a um paciente. O sistema guarda e controla a quantidade total permitida, valor unitário e final. O pacote cria "parcelas" na tabela `pacotes_parcelas` (ex.: "Sessão 1/4", "Sessão 2/4"). À medida que essas partes do combo vão sendo usadas, ocorre uma baixa no uso (`utilizado = 1` e `data_utilizacao`). Pode-se definir se o pagamento ocorre ao final (`pagamento_ao_final = 1`). 

### Módulo de Relatórios (`relatorio.php` / `ReportController`)
- **Responsabilidade:** Consolidar e exibir histórico de usos.
- **Regras:** Cruza dados entre os registros e os pacotes para emitir compilados que auxiliam no acerto de contas e análises de ocupação das salas da clínica em períodos de tempo.

### Módulo de Profissionais e Salas (Cadastros Básicos)
- **Responsabilidade:** Parametrizar opções primárias.
- **Regras:** Consiste em listas de nomes de **Salas** e nomes de **Profissionais**. A regra principal é que todos precisam ter nomes únicos na base de dados (chave `UNIQUE`) e contam com um status de "Ativação" (`ativo = 1` ou `0` na tabela) — o que permite inativar um item para o sistema sem que seja necessário excluí-lo (preservando o histórico de uso deles no passado).

### Módulo de Usuários e Controle de Acesso (ACL)
- **Responsabilidade:** Autenticação dos usuários administrativos da plataforma e controle de acesso aos diferentes módulos do sistema.
- **Regras:** Valida sessão com senhas sendo comparadas via hash criptográfico (`password_hash`). Caso o usuário possua registros de permissão para módulos amarrados à tabela `usuarios_modulos` (ex.: Tem permissão em Check-in e Relatórios, mas não em Pacotes), o acesso entra em vigor e as rotas são restritas; se a tabela não tiver nenhuma associação por módulo em nome dele, ele recebe "autoridade total" por padrão (uma possível lógica de compatibilidade com uma versão pré-implementação de perfis).

### Módulo de Autenticação (`login.php`, `logout.php`, `auth.php`)
- **Responsabilidade:** Controlar a entrada do sistema pela verificação de credenciais em uma página inicial isolada do sistema completo. Protege o aplicativo para evitar acessos indesejados ao Dashboard central.
