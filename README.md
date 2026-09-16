# Armory BR

> Vitrine de personagens para servidores privados de WoW 3.3.5 (WotLK).

O jogador cadastra seus personagens e vê todos organizados numa tela só, separados
por facção (Aliança/Horda), na ordem que ele mesmo definiu — do PC ou do celular.

Feito para quem joga em servidores privados brasileiros de Wrath of the Lich King,
onde não existe uma Armory oficial: todo dado é cadastrado manualmente pelo usuário.

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

## Avisos legais

- **World of Warcraft, seus nomes, ícones e arte pertencem à Blizzard
  Entertainment.** Este projeto não é afiliado, patrocinado nem endossado pela
  Blizzard. A licença MIT deste repositório cobre o código, não os ativos do jogo.
- O **TrinityCore é GPL-2.0**. O dump do banco de itens não é commitado neste
  repositório — apenas o comando que baixa e importa os dados.

## Privacidade

Cadastro é só usuário e senha — sem e-mail, sem recuperação de senha. Detalhes
completos na página `/privacy-policy` da aplicação.

## Licença

[MIT](LICENSE).
