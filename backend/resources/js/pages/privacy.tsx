import { Head, Link } from '@inertiajs/react';

export default function Privacy() {
    return (
        <div className="min-h-screen px-4 py-12">
            <Head title="Política de privacidade" />

            <div className="mx-auto max-w-2xl">
                <Link href="/" className="font-heading text-2xl font-bold text-parchment-100">
                    Armory BR
                </Link>

                <h1 className="mt-8 font-heading text-3xl font-bold text-parchment-100">
                    Política de privacidade
                </h1>

                <div className="mt-6 space-y-6 text-parchment-200">
                    <p>
                        O Armory BR é um projeto pessoal e open-source, sem fins lucrativos. Esta
                        página explica, sem juridiquês, o que fazemos com os seus dados.
                    </p>

                    <section>
                        <h2 className="font-heading text-lg font-semibold text-parchment-100">
                            O que coletamos
                        </h2>
                        <ul className="mt-2 list-disc space-y-1 pl-5">
                            <li>Seu nome de usuário.</li>
                            <li>O hash da sua senha (nunca a senha em texto puro).</li>
                            <li>
                                Os dados dos personagens que você cadastra: nome, facção, classe,
                                especialização e profissões.
                            </li>
                            <li>O endereço IP de acesso, registrado em log por tempo limitado.</li>
                        </ul>
                        <p className="mt-2">
                            Não pedimos e-mail, nome real ou qualquer outro dado pessoal além
                            desses.
                        </p>
                    </section>

                    <section>
                        <h2 className="font-heading text-lg font-semibold text-parchment-100">
                            Para que serve
                        </h2>
                        <p className="mt-2">
                            Só para o funcionamento do site: autenticar você, mostrar os seus
                            próprios personagens e manter a ordem que você definiu. Não vendemos,
                            compartilhamos ou usamos seus dados para publicidade.
                        </p>
                    </section>

                    <section>
                        <h2 className="font-heading text-lg font-semibold text-parchment-100">
                            Por quanto tempo guardamos
                        </h2>
                        <p className="mt-2">
                            Enquanto sua conta existir. Você pode apagar sua conta a qualquer
                            momento nas configurações — isso remove de verdade o usuário, os
                            personagens e as profissões cadastradas, sem cópia de segurança
                            escondida. Logs de acesso (IP) são descartados periodicamente.
                        </p>
                    </section>

                    <section>
                        <h2 className="font-heading text-lg font-semibold text-parchment-100">
                            Sem recuperação de senha
                        </h2>
                        <p className="mt-2">
                            Como não coletamos e-mail, não há como recuperar uma senha esquecida.
                            Se isso acontecer, a conta e os personagens são perdidos.
                        </p>
                    </section>

                    <section>
                        <h2 className="font-heading text-lg font-semibold text-parchment-100">
                            Contato
                        </h2>
                        <p className="mt-2">
                            Dúvidas, pedidos relacionados aos seus dados ou qualquer outra questão
                            sobre privacidade podem ser abertos como uma issue no{' '}
                            <a
                                href="https://github.com/pansiere/armory-br/issues"
                                target="_blank"
                                rel="noreferrer"
                                className="font-medium text-parchment-100 underline"
                            >
                                repositório do projeto no GitHub
                            </a>
                            .
                        </p>
                    </section>

                    <section>
                        <h2 className="font-heading text-lg font-semibold text-parchment-100">
                            Sobre World of Warcraft
                        </h2>
                        <p className="mt-2">
                            World of Warcraft, seus nomes, ícones e arte pertencem à Blizzard
                            Entertainment. O Armory BR não é afiliado, patrocinado nem endossado
                            pela Blizzard.
                        </p>
                    </section>
                </div>

                <Link
                    href="/login"
                    className="mt-10 inline-block text-sm text-parchment-300 underline"
                >
                    Voltar
                </Link>
            </div>
        </div>
    );
}
