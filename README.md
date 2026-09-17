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
- **Import de equipamento colado**: cola o texto exportado por qualquer addon de
  WotLK e o boneco inteiro é montado de uma vez, reconhecendo os itens pelo link
  (`item:ID`) e resolvendo o slot pela própria base de dados.
- **Perfil público opt-in**: cada personagem pode gerar um link (`/p/{token}`)
  sem necessidade de login, com imagem de preview (Open Graph) gerada na hora —
  cola no Discord da guilda e aparece o boneco montado.
- Raça, nível e **item level médio** (calculado a partir do equipamento) do
  personagem.
- Busca de itens com índice full-text (MySQL/MariaDB).

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
