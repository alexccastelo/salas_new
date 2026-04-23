# Análise de Migração para Framework

## Vale a pena migrar para um framework?

**Sim, mas depende do quanto o projeto vai crescer.** O projeto atual já possui uma boa estrutura MVC artesanal (sem framework), mas faltam recursos que precisarão ser reimplementados no futuro: roteamento robusto, migrations de banco de dados, autenticação padronizada, middlewares, etc.

## Sugestões de Frameworks

Existem dois caminhos principais: **Slim 4** (caminho mais curto) ou **Laravel** (caminho mais completo).

### 🥇 Slim 4 — menor esforço de migração

- Já usa **Twig**, **phpdotenv**, **PSR-4** — tudo que o Slim usa.
- É um microframework: mantém a arquitetura MVC atual e adiciona roteamento + middlewares.
- Permite migração gradual: pode coexistir com os arquivos legados da raiz.
- Ideal se o projeto for manter o tamanho atual.

### 🥈 Laravel — mais poder, mais trabalho

- Ecossistema completo: Eloquent ORM, migrations, filas, autenticação pronta, etc.
- Substitui o Twig pelo **Blade** (necessário reescrever as views).
- Requer uma reescrita mais profunda, mas entrega uma base muito mais sólida.
- Ideal se o sistema for crescer com novos módulos, usuários e integrações.

---

## Comparativo e Recomendação

| Critério | Slim 4 | Laravel |
|---|---|---|
| Esforço de migração | ⭐ Baixo | ⭐⭐⭐ Alto |
| Ecossistema | Minimalista | Completo |
| Escalabilidade | Médio | Alto |
| Curva de aprendizado | Baixa | Média |

**Se o projeto for crescer** (mais módulos, equipe, integrações): → **Laravel**

**Se quiser só organizar o que existe** com menos risco: → **Slim 4**

---

## Avaliação de Esforço de Migração (Foco no Crescimento e App Mobile)

Considerando que o projeto vai crescer para ter mais módulos integrados entre si e, no futuro, uma integração com **App Mobile**, a recomendação é **Laravel**.

### O que o projeto tem hoje

O código é bem escrito — MVC limpo, PDO puro, PSR-4. Isso facilita qualquer migração. Os pontos que exigem retrabalho são:

| Componente | Situação atual | Impacto na migração |
|---|---|---|
| **Router** | Artesanal, sem parâmetros de URL (`/sala/1`) | Alto — precisa reescrever rotas |
| **Models** | PDO puro, SQL manual | Médio (Slim) / Alto (Laravel/Eloquent) |
| **Views** | Twig ✅ | Baixo (Slim) / Alto (Laravel/Blade) |
| **Auth** | Sessão PHP manual | Médio (ambos têm autenticação própria) |
| **DB** | Singleton PDO | Médio — integrar com Eloquent ou Doctrine |
| **Legados na raiz** | `checkin.php`, `pacotes.php`, etc. | Alto — precisam ser portados |
| **Migrations** | Nenhuma — schema no `.db` | Alto (ambos usam migration files) |

### Cenário 1: Slim 4 (Esforço estimado: 3–5 semanas para dev solo)

**Vantagens na migração:**
- Twig e `.env` continuam iguais.
- Estrutura de Controllers/Models se mantém quase idêntica.

**Limitações futuras para app mobile:**
- ⚠️ Slim **não tem** geração de API JSON nativa estruturada pronta.
- ⚠️ Sem autenticação por token (JWT) nativa para apps.
- ⚠️ Sem scaffold automático de novos módulos.

### Cenário 2: Laravel (Esforço estimado: 6–10 semanas para dev solo)

**O que muda bastante:**
- Views: Twig → **Blade**
- Models: PDO puro → **Eloquent ORM**
- DB: Criar **migrations** para todo o schema existente.

**O que você ganha em troca (Essencial para App Mobile e Crescimento):**
- **Eloquent ORM:** Relacionamentos entre módulos muito mais fáceis.
- **Laravel Sanctum:** API RESTful com autenticação por token para o app mobile fica pronta rapidamente.
- **API Resources:** Transformação padronizada de dados JSON para o app.
- **Artisan CLI:** Geração automática de controllers, models, migrations (cada novo módulo leva horas, não dias).
- **Control de Schema e Testes:** Migrations versionadas e integração fácil com PHPUnit.

---

### Conclusão e Estratégia Recomendada

Para um cenário de **crescimento + aplicativo mobile**, o **Laravel é a resposta certa**. O esforço extra de migração (4-5 semanas adicionais em relação ao Slim) é um investimento que se paga rapidamente com a facilidade de criar APIs REST e novos módulos.

**Estratégia de migração recomendada (em fases para minimizar riscos):**

1. **Fase 1** — Instalar Laravel, portar schema como migrations, configurar DB.
2. **Fase 2** — Portar Models (PDO → Eloquent), um por vez.
3. **Fase 3** — Portar Controllers, mantendo views Twig temporariamente (usando pacotes como `twig-bridge`).
4. **Fase 4** — Portar views para Blade + criar rotas `routes/api.php` para o app mobile.
5. **Fase 5** — Remover completamente os arquivos legados da raiz.
