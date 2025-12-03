# Mapeamento Técnico do Banco de Dados DGOM_PAGTESOURO

## Estrutura do Banco de Dados

Após análise inicial, o banco de dados DGOM_PAGTESOURO apresenta uma estrutura focada no processamento de pagamentos através do sistema PagTesouro. Vou detalhar os principais componentes e seu funcionamento:

### Principais Tabelas

1. **tb_pgto** (Tabela Principal)
    - Armazena os dados principais de pagamento
    - Campos chave:
        - `id_pgto`: Identificador único do pagamento (VARCHAR(100))
        - `dt_criacao`: Data de criação do pagamento
        - `ds_situacao`: Situação atual do pagamento (PENDENTE, CONCLUIDO, etc.)
        - `cd_servico`: Código do serviço no PagTesouro Nacional
        - `cd_om`: OM recolhedora (atual)
        - `nome`: Nome do contribuinte
        - `cd_cpf`: CPF do contribuinte
        - `vr_principal`: Valor principal do pagamento
        - `cod_rubrica`: Código da rubrica (apenas uma por pagamento)
        - `tributavel`: Indica se a rubrica é tributável (0=não, 1=sim)
2. **tb_service** (Serviços)
    - Armazena configurações dos serviços disponíveis
    - Relaciona-se com a tabela principal através do campo `id_servico`
3. **tb_login** (Usuários)
    - Gerencia os usuários que têm acesso ao sistema
    - Não parece haver relacionamento direto com pagamentos
4. **tb_pgto_1** (possível tabela histórica)
    - Pode ser uma tabela para histórico ou backup de pagamentos

### Relacionamentos Identificados

O sistema utiliza um modelo de banco relativamente simples com poucos relacionamentos explícitos:

1. **tb_pgto** → **tb_service**
    - Relação N:1 (vários pagamentos para um serviço)
    - Chave: `id_servico`
2. Não foram identificadas tabelas específicas para:
    - Rubricas (embutido na tabela principal)
    - OMs/Organizações Militares (apenas códigos na tabela principal)
    - Detalhes de missão (inexistente, parte da proposta de alteração)

### Fluxo de Dados Técnico

O sistema funciona com o seguinte fluxo:

1. **Criação do Pagamento**:
   
   `Frontend → API DGOM → INSERT na tb_pgto (status=PENDENTE)`
   **Processamento do Pagamento**:
   
   API DGOM → API PagTesouro Nacional → Retorno URL do PIX
   → UPDATE na tb_pgto (campo situacao + URL)
   
   **Conclusão do Pagamento**:
   
   Callback PagTesouro → API DGOM → UPDATE na tb_pgto (situacao=CONCLUIDO)
   
   
   ### Aspectos Técnicos Relevantes

1. **Autenticação/Autorização**:
    - Utiliza token JWT para comunicação com PagTesouro Nacional
    - Token observado no dump: `eyJhbGciOiJSUzI1NiJ9.eyJzdWIiOiI3NzMyMDAifQ...`
2. **Conexão com PagTesouro Nacional**:
    - Endpoints identificados:
        - Homologação: `https://dpagtesourohmg.mb:3000`
        - Produção: `https://pagtesouro.dgom.mb:3000`
3. **Códigos de Serviço**:
    - Homologação: `1541` (todos os tipos)
    - Produção: `11859` (Pagamento Pessoal) e `11860` (SISRES)
4. **Geração de IDs**:
    - IDs de pagamentos parecem seguir formato específico
    - Não há sequências explícitas para geração de IDs
5. **Limitações Arquiteturais**:
    - Suporte apenas a uma rubrica por pagamento
    - Ausência de tabela específica para missões GART REP
    - Campos específicos embutidos na tabela principal
      
      
      Modelo Conceitual Atual
      [tb_login] ← Autenticação/Acesso ao Sistema | v [tb_service] ← Configurações dos Serviços | v [tb_pgto] ← Dados Principais de Pagamento - id_pgto (PK) - ds_situacao (Status) - cd_servico (Serviço no PagTesouro Nacional) - nome (Contribuinte) - cd_cpf (CPF) - vr_principal (Valor) - cod_rubrica (Código Rubrica - embutido) - nome_rubrica (Nome Rubrica - embutido) - tributavel (Tributável - sim/não) - cd_om (OM recolhedora - embutido)
      
      ## Limitações Técnicas Identificadas

1. **Modelagem Simplificada**
    - Todos os dados de pagamento concentrados em uma única tabela
    - Ausência de normalização para rubricas e missões
2. **Escalabilidade**
    - Ausência de suporte nativo para múltiplas rubricas
    - Ausência de histórico detalhado de alterações
3. **Manutenibilidade**
    - Campos específicos embutidos diretamente na tabela principal
    - Relacionamentos implícitos em vez de chaves estrangeiras explícitas
4. **Validação de Dados**
    - Validação feita principalmente na aplicação, não no banco
    - Poucos constraints ou triggers para garantir integridade

## Recomendações Técnicas

Além das alterações solicitadas, recomendo considerar:

1. **Refatoração para Normalização**
    - Criar tabelas específicas para rubricas, OMs, missões
    - Transformar relacionamentos implícitos em explícitos
2. **Indexação Estratégica**
    - Adicionar índices para campos frequentemente consultados
    - Exemplo: `CREATE INDEX idx_situacao_data ON tb_pgto(ds_situacao, dt_situacao)`
3. **Auditoria de Alterações**
    - Implementar sistema de log de alterações
    - Criar triggers para registro automático de mudanças importantes
4. **Validação no Banco**
    - Implementar constraints para garantir integridade
    - Adicionar checks para valores válidos (ex: situações permitidas)

Este mapeamento técnico fornece uma visão geral do banco de dados e seu funcionamento atual, destacando suas características principais e limitações que precisam ser consideradas ao implementar as novas funcionalidades solicitadas.
