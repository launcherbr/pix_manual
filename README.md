# Módulo de Pagamento PIX Manual para WHMCS

Um gateway de pagamento nativo e leve para WHMCS que permite receber pagamentos via PIX diretamente na sua conta bancária, sem intermediários ou taxas. 

O módulo gera dinamicamente o Payload EMV (BR Code) exigido pelo Banco Central do Brasil, exibe o QR Code na fatura do cliente e inclui um fluxo facilitado para o envio do comprovante de pagamento via ticket de suporte.

## 🚀 Funcionalidades

* **QR Code Dinâmico:** Geração de QR Code em tempo real na tela da fatura com o valor exato da cobrança.
* **PIX Copia e Cola:** Campo de texto com botão de cópia em um clique para facilitar o pagamento via celular.
* **Cálculo Automático de CRC16:** Validação nativa do código alfanumérico no padrão do BACEN.
* **Envio de Comprovante Integrado:** Botão na própria fatura que redireciona o cliente para o seu departamento de cobrança, já preenchendo o assunto do ticket com o número da fatura.
* **Zero Taxas:** O valor vai direto para a sua chave PIX, sem gateways terceirizados.

## 📋 Requisitos

* WHMCS 7.x ou superior (Testado e compatível com as versões mais recentes 8.x).
* PHP 7.2 a 8.3.

## 🛠️ Instalação

1. Faça o download ou clone este repositório.
2. Acesse os arquivos da sua instalação do WHMCS via FTP, painel de controle ou SSH.
3. Copie o arquivo `pix_manual.php` para dentro do diretório de gateways do seu WHMCS:
   `[SEU_WHMCS]/modules/gateways/pix_manual.php`

## ⚙️ Configuração

Após enviar o arquivo, siga os passos abaixo no painel administrativo do WHMCS:

1. Navegue até **Configurações do Sistema** (System Settings) > **Portais de Pagamento** (Payment Gateways).
2. Na aba **Todos os Portais de Pagamento** (All Payment Gateways), procure por **PIX Manual - QR Code** e clique para ativar.
3. Na aba **Gerenciar Portais Existentes** (Manage Existing Gateways), configure os seguintes campos obrigatórios:

   * **Tipo de Chave:** Escolha o tipo de chave (apenas para referência visual).
   * **Chave PIX:** Insira sua chave PIX. 
     * ⚠️ *Atenção:* Se for número de telefone, é obrigatório incluir o código do país (`+55`). Exemplo: `+5511999999999`.
   * **Nome do Recebedor:** Seu nome ou o nome da sua empresa (Máximo de 25 caracteres, sem acentos).
   * **Cidade:** A cidade do titular da conta (Máximo de 15 caracteres, sem acentos).
   * **ID do Depto de Suporte:** O ID numérico do seu departamento de faturamento/cobrança no WHMCS (ex: `1`, `2`, `3`). É para este departamento que o botão de comprovante enviará os clientes.

4. Clique em **Salvar Alterações**.

## 💡 Como descobrir o ID do Departamento de Suporte?

1. No WHMCS, vá em **Configurações do Sistema** > **Departamentos de Suporte**.
2. Clique no ícone de editar (lápis) no departamento desejado (ex: Financeiro).
3. Olhe a URL no seu navegador. Você verá algo como `action=edit&id=2`. O número após o `id=` é o ID do seu departamento.

## ⚠️ Avisos Importantes

* **Validação Manual:** Por ser um módulo *manual*, as faturas **não** serão marcadas como pagas automaticamente. Sua equipe precisará conferir o extrato bancário (ou o comprovante enviado pelo ticket) e dar baixa manual na fatura pelo painel do WHMCS.
* **API de QR Code:** O módulo utiliza a API gratuita do `qrserver.com` para renderizar a imagem do QR Code a partir do Payload gerado. 

## 📝 Licença

Este projeto está licenciado sob a licença MIT - veja o arquivo [LICENSE](LICENSE) para mais detalhes.

---

> ## ⚠️ Disclaimer Legal e Comercial
>
> Este módulo é de propriedade da <strong>Launcher Tecnologia Ltda ME</strong>, inscrita no CNPJ: <strong>26.651.889/0001-60</strong>, com marca comercial e operação sob a fantasia <strong>Launcher Tech</strong>.
>
> O software é disponibilizado exclusivamente como <strong>módulo comercial com distribuição gratuita</strong>, sendo concedido por cortesia para uso e avaliação, sem qualquer intenção de venda, revenda, comercialização ou distribuição comercial indevida.
>
> É expressamente proibida a venda, revenda, reprodução em massa, redistribuição com fins lucrativos ou uso em contextos que configurem comercialização do código ou de versões derivadas sem autorização prévia e formal da titular dos direitos.
>
> Qualquer uso, adaptação ou redistribuição deve respeitar os termos de propriedade intelectual da empresa e a finalidade de uso cortês e não comercial.
>
