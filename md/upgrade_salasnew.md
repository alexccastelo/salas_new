# Upgrade de Módulos - Agenda e Serviços

Vamos criar um módulo **Agenda** similar ao **Google Calendar** e integrado com ele para que haja espelhamento dos agendamentos feitos tanto na agenda do sistema, como no Google Calendar e como necessidade desse módulo, vamos também criar o módulo Serviços e aprimorar os cadastros de Salas e Profissionais.

## 1. Agenda

1. Cada slot de atendimento terá 60 min (50 min de atendimento efetivo e 10 min de transição para o próximo) de atendimento como padrão, mas podendo ser alterado, conforme o cadastro de serviços.

2. Cada agendamento estará vinculado ao paciente, ao profissional e à sala onde acontecerá ou ainda se for on-line.

3. Cada agendamento incluído em uma plataforma (agenda sistema ou Google) deve ser replicada automaticamente na outra.

4. Não será possível agendar um atendimento com o mesmo profissional e/ou mesma sala no mesmo horário, o sistema deve alertar e impedir.

5. Deve ser possível configurar a faixa de horário de atendimento da clínica (início, pausa e final).

6. Incluir possibilidade de bloqueio de agenda por profissional, por sala ou da clínica toda.

7. Incluir cadastro de feriados e dias onde a clínica não funcionará

8. Cada agendamento deve enviar alertas ao profissional:
   - quando o agendamento for realizado
   - se o agendamento for a 48 horas ou mais, enviar outro alerta de lembrete 24 hs antes
   - resumo de sua agenda na clínica do dia seguinte, sendo disparado às 19h do dia anterior

## 2. Serviços

### Cadastro de Serviços Oferecidos pela Clínica

1. Nome
2. Descrição
3. Especialidade
4. Tempo de atendimento
5. Quantidade de sessões
6. Possível vinculação com outro serviço
7. Valor cobrado
   - Base
   - À vista
   - À prazo
   - Quantidade de parcelas

## 3. Salas

1. Nome
2. Descrição
3. Tipo de atendimentos (serviços) possíveis - vinculado do cadastro de serviços
4. Quantidade de pessoas possíveis
5. Cor ao aparecer na agenda

## 4. Profissionais

### Incluir no Cadastro

1. Celular
2. E-mail
3. Chave PIX
4. Profissão
5. Especialidade
6. Conselho
7. Número no conselho
8. Tipo de contrato (PJ, CLT, Eventual)
9. Percentual de repasse
10. Cor para exibir na agenda
