# Armory BR

> Vitrine de personagens para servidores privados de WoW 3.3.5 (WotLK).

O jogador cadastra seus personagens e vê todos organizados numa tela só, separados
por facção (Aliança/Horda), na ordem que ele mesmo definiu — do PC ou do celular.

Feito para quem joga em servidores privados brasileiros de Wrath of the Lich King,
onde não existe uma Armory oficial: todo dado é cadastrado manualmente pelo usuário.

## Funcionalidades

- Cadastro e login, isolamento total entre usuários (Policies), exclusão de conta
  apagando tudo de verdade (LGPD).
- Tela principal com as duas colunas de facção, contador em cada uma, e
  arrastar-e-soltar para reordenar — arrastar de uma coluna pra outra troca a
  facção do personagem. Responsivo de verdade (colunas empilham no celular).
- **Boneco visual de equipamento**, com os 19 slots equipáveis do 3.3.5, ícone
  real de cada item e borda colorida por qualidade (cinza/branco/verde/azul/roxo/laranja).
- **Sockets e gemas**: item com socket (vermelho/amarelo/azul/meta) ganha selos
  clicáveis no boneco — busca só gemas compatíveis com a cor daquele socket.
- **Dual spec**: até 2 especializações por personagem (as 3 árvores de talento
  de cada classe, com ícone), cada uma com o seu próprio conjunto de
  equipamento — trocar de aba no boneco troca o gear junto, igual no jogo.
- **Import de equipamento colado**: cola o texto exportado pelo addon
  [ArmoryBRExport](#importando-equipamento) (ou o link de qualquer item
  colado do chat) e o boneco inteiro é montado de uma vez — sem precisar
  cadastrar item por item.
- **Perfil público opt-in**: cada personagem pode gerar um link (`/p/{token}`)
  sem necessidade de login, com imagem de preview (Open Graph) gerada na hora —
  cola no Discord da guilda e aparece o boneco montado.
- Raça, nível e **item level médio** (calculado a partir do equipamento) do
  personagem.
- Busca de itens com índice full-text (MySQL/MariaDB).

## Importando equipamento

Não existe (ainda) um jeito de importar o personagem inteiro — nome, raça,
classe, nível e profissões continuam sendo cadastrados na mão, uma vez por
personagem, porque não há acesso ao banco do servidor pra puxar isso
automaticamente. O que a importação resolve é a parte chata de configurar o
**equipamento**, item por item.

### Com o addon ArmoryBRExport (recomendado)

Nenhum addon de terceiro pronto funciona de verdade nesse client. O
[SimulationCraft](https://github.com/simulationcraft/simc-addon) oficial só
declara suporte a clientes de retail moderno. O
[WowSims Exporter](https://github.com/wowsims/exporter) também não —
apesar do nome sugerir suporte a "Wrath", ele foi escrito pra API que só
existe em clients modernos da Blizzard (`WOW_PROJECT_ID`, `C_AddOns`,
`C_Engraving`...), nenhuma delas presente no 3.3.5a original de 2010 que
servidores privados usam — ele crasha com erro de Lua já no carregamento.

Por isso escrevi um addon próprio e minimalista pra isso,
**ArmoryBRExport**: sem libs externas, só API nativa antiga garantida de
existir em qualquer client 3.3.5a.

1. Baixe a pasta `ArmoryBRExport` do repositório
   [wotlk-addons](https://github.com/Pansiere/wotlk-addons/tree/main/ArmoryBRExport)
   pra `Interface/AddOns` e entre no jogo com o personagem que quer importar
   (se o addon acabou de ser instalado, é preciso deslogar até a tela de
   seleção de personagem pelo menos uma vez antes de aparecer na lista).
2. Digite `/armorybr` (ou `/abr`) na barra de chat. Abre uma janela com o
   equipamento já selecionado — `Ctrl+C` pra copiar.
3. Na Armory BR, cadastre o personagem (nome/raça/classe/spec(s)/nível) se
   ainda não existir, entre na edição dele e cole o texto na caixa **"Colar
   equipamento"**. Cada peça é reconhecida pelo nome do slot (`head=`,
   `main_hand=`, etc.) e cai automaticamente no lugar certo do boneco — **as
   gemas engastadas também são importadas** junto com cada item, direto nos
   sockets certos. Personagem com dual spec: a caixa de import sempre
   preenche a aba de spec selecionada no momento — o addon só exporta o
   equipamento ativo, então dá
   pra colar uma vez em cada aba se quiser montar as duas.

A caixa de import também reconhece o formato de texto do SimC
(`head=algum_item,id=12345,...`), caso algum dia surja um port desse addon
pra 3.3.5a — não precisa trocar nada no site se isso acontecer.

### Sem addon

Funciona também sem instalar nada: dentro do jogo, dê `Shift+clique` em cada
item equipado no seu painel de personagem — isso insere o link do item na
caixa de chat. Selecione o texto, copie e cole na mesma caixa de "Colar
equipamento". É mais repetitivo (até 19 cliques), mas usa só recursos
nativos do cliente.

Colar de novo (depois de trocar de gear) é seguro — a importação atualiza os
mesmos slots, nunca duplica.

## Stack

- **Backend** (`backend/`): Laravel + Inertia.js + React (TypeScript) + Tailwind CSS.
  SQLite em desenvolvimento, MySQL/MariaDB em produção.
- **Mobile** (`mobile/`): planejado para depois da v1 (iOS e Android).

## Rodando localmente

```bash
cd backend
composer setup   # composer install, .env, chave da app, migrations, build do front
composer dev     # servidor + fila + vite, tudo junto
```

## Testes

```bash
cd backend
vendor/bin/pest
```

## Base de itens

```bash
cd backend
php artisan items:import
```

Baixa `item_template.sql` do [AzerothCore-wotlk](https://github.com/azerothcore/azerothcore-wotlk)
(pinado num commit fixo, pra ficar reproduzível) e popula a tabela `items` —
nome, qualidade, slot, item level. **Só funciona com MySQL/MariaDB** (o dump é
MySQL); precisa do client `mysql` disponível no PATH.

Em seguida baixa o mapeamento item → ícone do
[nexus-devs/wow-classic-items](https://github.com/nexus-devs/wow-classic-items)
(MIT, também pinado) — o AzerothCore não redistribui esse dado (vem extraído
do cliente do jogo, licenciado pela Blizzard), mas esse projeto já publica a
relação pronta. Os ícones em si não são baixados nem commitados: a app só
guarda o nome e monta a URL na hora, apontando pro CDN do Wowhead
(`wow.zamimg.com`) — o mesmo hotlink que praticamente todo addon/site de WoW
usa.

Por fim resolve a cor de cada gema (pra bater com o socket certo no boneco).
O AzerothCore não distribui essa informação como SQL (`gemproperties_dbc.sql`
do repositório só tem schema, sem linha nenhuma — isso normalmente vem do DBC
do cliente do jogo, não do banco do servidor), então a cor é lida do próprio
texto de descrição da gema no `item_template` (ex.: "Matches a Red Socket.").

## Avisos legais

- **World of Warcraft, seus nomes, ícones e arte pertencem à Blizzard
  Entertainment.** Este projeto não é afiliado, patrocinado nem endossado pela
  Blizzard. A licença MIT deste repositório cobre o código, não os ativos do jogo.
- O **AzerothCore é GPL-2.0**. O dump do banco de itens não é commitado neste
  repositório — apenas o comando que baixa e importa os dados.

## Privacidade

Cadastro pede usuário, e-mail e senha. O e-mail é coletado para permitir
recuperação de conta no futuro — por enquanto nenhum e-mail é enviado
automaticamente. Detalhes completos na página `/privacy-policy` da aplicação.

## Licença

[MIT](LICENSE).
