Evolution API v2.2.3 — Endpoints Completos
Status: Compilado e organizado
Data: 13/10/2025

Observações importantes
- A Evolution API não publica uma coleção Postman separada rotulada “v2.2.3”. O patch 2.2.3 foi uma atualização de correções e melhorias internas.
- Os endpoints da linha 2.2.x são os mesmos usados na coleção pública “v2.2.2” (mantidos em 2.2.3). As rotas abaixo foram extraídas dessa coleção oficial e da documentação v2.
- Variáveis de path: substitua {instance} pelo nome da instância.
- Autorização: header `apikey: <sua-chave>`.

==============================
GET /        — Get Information (saúde da API)
==============================
Descrição: Retorna informações gerais da API/servidor (status, versão, ambiente) para verificação rápida de saúde (health-check).

--------------------------------
INSTANCES
--------------------------------
POST   /instance/create
- Cria uma nova instância (sessão do WhatsApp) e retorna os dados básicos para uso posterior.

GET    /instance/fetchInstances
- Lista todas as instâncias existentes/acessíveis junto com seus estados básicos.

GET    /instance/connect/{instance}
- Inicia/força o processo de conexão da instância informada; em clientes headless, pode retornar QR Code/estado de pareamento.

POST   /instance/restart/{instance}
- Reinicia a instância (útil para recuperar de falhas, aplicar algumas configurações, ou renovar sessão).

POST   /instance/setPresence/{instance}
- Define a presença/atividade do usuário (digitando, gravando áudio, online/offline) exposta ao contato.

GET    /instance/connectionState/{instance}
- Retorna o estado atual da conexão (ex.: OPEN, CLOSE, PAIRING, TIMEOUT, LOGGED_OUT).

DELETE /instance/logout/{instance}
- Faz logout da instância (encerra sessão autenticada, exigindo novo pareamento para voltar).

DELETE /instance/delete/{instance}
- Remove a instância do servidor (e geralmente limpa dados associados, conforme configuração).

--------------------------------
PROXY
--------------------------------
POST   /proxy/set/{instance}
- Define um proxy (host/porta/credenciais) para o tráfego dessa instância.

GET    /proxy/find/{instance}
- Retorna a configuração de proxy atualmente aplicada à instância.

--------------------------------
SETTINGS
--------------------------------
POST   /settings/set/{instance}
- Define parâmetros diversos da instância (ex.: comportamento de download, timeouts, limitações, webhooks globais).

GET    /settings/find/{instance}
- Obtém as configurações atuais da instância.

--------------------------------
WEBHOOK
--------------------------------
POST   /webhook/set/{instance}
- Registra/atualiza a URL de Webhook e preferências (eventos que serão enviados) para a instância.

GET    /webhook/find/{instance}
- Retorna a configuração de Webhook em vigor (URL, status, eventos ativos).

--------------------------------
SEND MESSAGE
--------------------------------
POST   /message/sendText/{instance}
- Envia mensagem de texto para um ou mais destinatários (suporta formatação básica e mentions).

POST   /message/sendMedia/{instance}          (suporta URL e arquivo)
- Envia mídia (imagem, vídeo, áudio, documento) por URL/Base64 ou upload multipart para um ou mais destinatários.

POST   /message/sendPTV/{instance}
- Envia PTV (vídeo curto estilo “vídeo de câmera” do WhatsApp) por URL/Base64.

POST   /message/sendPTVFile/{instance}
- Envia PTV por upload de arquivo (multipart/form-data).

POST   /message/sendNarratedAudio/{instance}
- Envia áudio narrado (formato WhatsApp/ptt) para contatos/chats.

POST   /message/sendStatus/{instance}         (Status/Stories)
- Publica um status/story (texto/mídia) no perfil da instância.

POST   /message/sendSticker/{instance}
- Envia figurinha (sticker); aceita imagem estática/animada convertendo para sticker conforme suporte.

POST   /message/sendLocation/{instance}
- Envia localização (latitude/longitude e opcionalmente nome/endereço).

POST   /message/sendContact/{instance}
- Envia um contato (vCard) ou lista de contatos para o destinatário.

POST   /message/sendReaction/{instance}
- Reage a uma mensagem específica (ex.: 👍❤️😂) passando a chave/ID da mensagem alvo.

POST   /message/sendPoll/{instance}
- Cria e envia uma enquete (título, opções, voto único/múltiplo).

POST   /message/sendList/{instance}
- Envia mensagem de lista (título, seções e itens selecionáveis) para fluxos de escolha.

POST   /message/sendButton/{instance}
- Envia mensagem com botões (CTA, respostas rápidas) em formato compatível com o cliente WhatsApp.

--------------------------------
CALL
--------------------------------
POST   /call/offer/{instance}                 (Fake Call)
- Simula uma oferta de chamada (apenas para testes/fluxos internos; não disca uma chamada real para o número).

--------------------------------
CHAT (conversas e mensagens)
--------------------------------
POST   /chat/checkIsOnWhatsApp/{instance}
- Verifica se um número (ou lista) está registrado no WhatsApp.

POST   /chat/readMessages/{instance}
- Marca mensagens como lidas (read receipts) em um chat/conversa.

POST   /chat/archiveChat/{instance}
- Arquiva a conversa especificada (oculta da lista principal do cliente).

POST   /chat/markChatAsUnread/{instance}
- Marca a conversa como não lida (badge/indicador).

DELETE /chat/deleteMessage/{instance}
- Exclui uma mensagem. Dependendo do corpo, pode apagar para todos (quando suportado) ou só localmente.

POST   /chat/fetchProfilePicture/{instance}
- Busca a URL/fonte da foto de perfil de um número/contato.

POST   /chat/getBase64FromMediaMessage/{instance}
- Baixa e retorna a mídia de uma mensagem existente em Base64 (útil para salvar/processar).

POST   /chat/updateMessage/{instance}
- Atualiza uma mensagem enviada (quando o WhatsApp suporta, p.ex. editar texto ou legenda).

POST   /chat/sendPresence/{instance}
- Envia presença (digitando, gravando áudio, online) para o chat, sem enviar mensagem.

POST   /chat/updateBlockStatus/{instance}
- Altera o estado de bloqueio de um contato (bloquear/desbloquear).

POST   /chat/findContacts/{instance}
- Procura contatos cadastrados/sincronizados; pode aceitar filtros/termos.

POST   /chat/findMessages/{instance}
- Pesquisa mensagens por critérios (data, remetente, palavras-chave, ids).

POST   /chat/findStatusMessage/{instance}
- Consulta status/stories publicados por contatos (metadados/IDs).

POST   /chat/findChats/{instance}
- Lista/filtra conversas com paginação e critérios (arquivado, silenciado, não lido etc.).

--------------------------------
PROFILE SETTINGS
--------------------------------
POST   /chat/fetchBusinessProfile/{instance}
- Obtém informações do perfil comercial (business profile) quando aplicável (nome, descrição, endereço, categorias).

POST   /chat/fetchProfile/{instance}
- Obtém dados do perfil da conta (nome, “about”, foto, configurações).

POST   /chat/updateProfileName/{instance}
- Atualiza o nome de exibição do perfil da instância.

POST   /chat/updateProfileStatus/{instance}
- Atualiza o “about”/status de texto do perfil.

POST   /chat/updateProfilePicture/{instance}
- Define/atualiza a foto de perfil (arquivo/URL) da instância.

DELETE /chat/removeProfilePicture/{instance}
- Remove a foto de perfil atual da instância.

GET    /chat/fetchPrivacySettings/{instance}
- Retorna as configurações de privacidade (visto por último, foto, recados, confirmação de leitura etc.).

POST   /chat/updatePrivacySettings/{instance}
- Atualiza as configurações de privacidade (escopo: todos, meus contatos, ninguém, exceções).

--------------------------------
LABEL
--------------------------------
GET    /label/findLabels/{instance}
- Lista todas as labels/etiquetas disponíveis (e counts quando suportado).

POST   /label/handleLabel/{instance}
- Cria/atualiza/aplica/remove labels a chats/mensagens conforme o corpo enviado.

--------------------------------
GROUP
--------------------------------
POST   /group/create/{instance}
- Cria um novo grupo com participantes iniciais e assunto.

POST   /group/updatePicture/{instance}
- Atualiza a foto do grupo (upload/URL).

POST   /group/updateSubject/{instance}
- Atualiza o assunto (título) do grupo.

POST   /group/updateDescription/{instance}
- Define/atualiza a descrição do grupo.

GET    /group/inviteCode/{instance}
- Obtém o código de convite atual do grupo.

POST   /group/revokeInvite/{instance}
- Revoga o código de convite e gera um novo.

POST   /group/sendInviteUrl/{instance}
- Envia o link de convite do grupo a um contato (ou retorna para uso externo).

GET    /group/inviteInfo/{instance}?inviteCode=<code>
- Consulta informações de um convite (validade, grupo, etc.) pelo código.

GET    /group/findGroupByJid/{instance}?groupJid=<jid>
- Busca informações de um grupo através do JID (id interno do WhatsApp).

GET    /group/fetchAllGroups/{instance}
- Lista todos os grupos que a instância participa (com paginação quando disponível).

GET    /group/findParticipants/{instance}?groupJid=<jid>
- Lista os participantes de um grupo (admins, comuns, status).

POST   /group/updateParticipant/{instance}
- Adiciona/remove participantes e promove/remove cargos de admin.

POST   /group/updateSetting/{instance}
- Altera configurações do grupo (quem pode enviar, quem pode editar info, aprovar entrada, etc.).

POST   /group/toggleEphemeral/{instance}
- Liga/desliga mensagens temporárias (prazo de autoapagamento).

DELETE /group/leave/{instance}
- Sai do grupo especificado (a instância abandona).

--------------------------------
INTEGRATIONS — WebSocket / MQ / Webhook
--------------------------------
POST   /websocket/set/{instance}
- Ativa/configura o envio de eventos por WebSocket (URL/credenciais).

GET    /websocket/find/{instance}
- Retorna a configuração de WebSocket atual.

POST   /rabbitmq/set/{instance}
- Configura publicação de eventos em uma fila/exchange RabbitMQ (host/porta/credenciais).

GET    /rabbitmq/find/{instance}
- Retorna configuração atual do RabbitMQ para a instância.

POST   /sqs/set/{instance}
- Configura envio de eventos para Amazon SQS (queue URL/keys/region).

GET    /sqs/find/{instance}
- Retorna a configuração atual de SQS da instância.

POST   /chatwoot/set/{instance}
- Configura integração com Chatwoot (inbox/token/parâmetros).

GET    /chatwoot/find/{instance}
- Retorna a configuração atual do Chatwoot.

--------------------------------
INTEGRATIONS — TYPEBOT
--------------------------------
POST   /typebot/default/settings/{instance}         (Set Default Settings)
- Define configurações padrão do Typebot (endpoint/keys/variáveis) para a instância.

GET    /typebot/default/fetchSettings/{instance}    (Fetch Default Settings)
- Obtém as configurações padrão atuais do Typebot.

POST   /typebot/create/{instance}                   (Create Typebot)
- Cria um fluxo/bot Typebot associado.

GET    /typebot/find/{instance}                     (Find Typebots)
- Lista bots/fluxos Typebot já criados/associados.

GET    /typebot/fetch/{instance}                    (Fetch Typebot)
- Retorna detalhes de um bot/fluxo específico.

PUT    /typebot/update/{instance}                   (Update Typebot)
- Atualiza propriedades de um bot/fluxo Typebot.

DELETE /typebot/delete/{instance}                   (Delete Typebot)
- Remove um bot/fluxo Typebot associado.

POST   /typebot/start/{instance}                    (Start Typebot)
- Inicia/ativa a execução do bot/fluxo em conversas elegíveis.

POST   /typebot/session/changeStatus/{instance}     (Change Session Status)
- Altera o status de uma sessão do Typebot (pausar/retomar).

GET    /typebot/session/fetch/{instance}            (Fetch Sessions)
- Lista/consulta sessões do Typebot (estado, histórico, métricas).

--------------------------------
INTEGRATIONS — EVOLUTION BOT
--------------------------------
POST   /evolutionBot/create/{instance}              (Create Bot)
- Cria um bot nativo “Evolution Bot” para automações/fluxos.

GET    /evolutionBot/find/{instance}                (Find Bots)
- Lista bots Evolution existentes.

GET    /evolutionBot/fetch/{instance}               (Fetch Bot)
- Obtém detalhes de um bot Evolution específico.

PUT    /evolutionBot/update/{instance}              (Update Bot)
- Atualiza propriedades do bot (regras, prompts, gatilhos).

DELETE /evolutionBot/delete/{instance}              (Delete Bot)
- Remove o bot Evolution.

POST   /evolutionBot/settings/{instance}            (Set Settings Bot)
- Define configurações do bot (tokens, endpoints externos, variáveis).

GET    /evolutionBot/fetchSettings/{instance}       (Find Settings Bot)
- Retorna as configurações vigentes do bot.

POST   /evolutionBot/changeStatus/{instance}        (Change Bot Status)
- Muda status/atividade do bot (on/off).

GET    /evolutionBot/session/fetch/{instance}       (Fetch Evolution Bot Session)
- Consulta sessões/execuções do bot (logs, estado).

--------------------------------
INTEGRATIONS — OPENAI
--------------------------------
POST   /openai/default/settings/{instance}          (Set Default Settings)
- Define configurações padrão (modelo/chaves/opções) para integrações OpenAI.

GET    /openai/default/fetchSettings/{instance}     (Fetch Default Settings)
- Retorna as configurações padrão atuais do OpenAI.

POST   /openai/creds/set/{instance}                 (Set OpenAI Creds)
- Registra credenciais (API keys/organizações) usadas pela integração.

GET    /openai/creds/get/{instance}                 (Get OpenAI Creds)
- Retorna as credenciais armazenadas (mascaradas quando aplicável).

DELETE /openai/creds/delete/{instance}              (Delete OpenAI Creds)
- Remove as credenciais registradas.

POST   /openai/create/{instance}                    (Create OpenAI Bot)
- Cria um bot/assistente baseado em OpenAI.

GET    /openai/find/{instance}                      (Find OpenAI Bots)
- Lista bots OpenAI existentes.

GET    /openai/fetch/{instance}                     (Fetch OpenAI Bot)
- Obtém detalhes de um bot OpenAI específico.

PUT    /openai/update/{instance}                    (Update OpenAI Bot)
- Atualiza propriedades do bot (prompt, tools, temperature etc.).

DELETE /openai/delete/{instance}                    (Delete OpenAI Bot)
- Exclui um bot OpenAI.

POST   /openai/session/changeStatus/{instance}      (Change OpenAI Session Status)
- Altera o status/execução de sessões do bot.

GET    /openai/session/fetch/{instance}             (Fetch OpenAI Sessions)
- Consulta sessões/execuções do bot (logs, métricas).

--------------------------------
INTEGRATIONS — DIFY
--------------------------------
POST   /dify/create/{instance}                      (Create Dify Bot)
- Cria um bot/fluxo Dify.

GET    /dify/find/{instance}                        (Find Dify Bots)
- Lista bots Dify existentes.

GET    /dify/fetch/{instance}                       (Find Dify Bot)
- Retorna detalhes de um bot Dify específico.

PUT    /dify/update/{instance}                      (Update Dify Bot)
- Atualiza propriedades do bot Dify.

POST   /dify/settings/{instance}                    (Set Dify Settings)
- Define parâmetros/credenciais para Dify.

GET    /dify/fetchSettings/{instance}               (Find Dify Settings)
- Retorna configurações vigentes do Dify.

POST   /dify/changeStatus/{instance}                (Change Status Bot)
- Altera o status/atividade do bot Dify.

GET    /dify/findStatus/{instance}                  (Find Status Bot)
- Consulta o status atual/último estado do bot Dify.

--------------------------------
INTEGRATIONS — FLOWISE
--------------------------------
POST   /flowise/create/{instance}                   (Create Flowise Bot)
- Cria um bot/fluxo Flowise.

GET    /flowise/find/{instance}                     (Find Flowise Bots)
- Lista bots Flowise disponíveis.

GET    /flowise/fetch/{instance}                    (Find Flowise Bot)
- Detalhes de um bot Flowise específico.

POST   /flowise/update/{instance}                   (Update Flowise Bot)
- Atualiza propriedades do bot Flowise.

DELETE /flowise/delete/{instance}                   (Delete Flowise Bot)
- Remove um bot Flowise.

POST   /flowise/settings/{instance}                 (Set Settings Flowise Bots)
- Define configurações e credenciais para Flowise.

GET    /flowise/fetchSettings/{instance}            (Find Flowise settings)
- Retorna configurações vigentes do Flowise.

POST   /flowise/session/changeStatus/{instance}     (Change Status Session)
- Altera o status de sessões Flowise.

GET    /flowise/session/fetch/{instance}            (Find Sessions Flowise)
- Consulta sessões do Flowise.

--------------------------------
INTEGRATIONS — CHANNEL (WhatsApp Cloud API Oficial)
--------------------------------
POST   /message/sendTemplate/{instance}             (Send Template)
- Envia mensagens via modelo (template) usando o canal Cloud API Oficial.

POST   /template/create/{instance}                  (Create Template)
- Cria/cadastra um novo template (para aprovação/uso).

GET    /template/find/{instance}                    (Find Templates)
- Lista/consulta templates cadastrados e seus estados.

--------------------------------
STORAGE — S3 / MinIO
--------------------------------
POST   /storage/s3/getMedia/{instance}
- Baixa mídia (por ID/URL interna) do storage S3/MinIO e retorna seu conteúdo codificado ou stream.

POST   /storage/s3/getMediaUrl/{instance}
- Gera/recupera uma URL de acesso (presign) temporária para a mídia no S3/MinIO.

--------------------------------
MÉTRICAS (quando disponível)
--------------------------------
GET    /metrics
- Exposição de métricas para observabilidade (padrão Prometheus/OpenMetrics quando habilitado).

Fim do documento.
