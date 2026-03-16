<?php

namespace App\Services;

use App\Repositories\ClienteRepository;
use App\Views\AlertRenderer;
use Throwable;

class ClienteService
{
    private $repository;

    public function __construct(ClienteRepository $repository)
    {
        $this->repository = $repository;
    }

    public function defaultData()
    {
        return [
            'nome_cliente' => '',
            'telefone_contato' => '',
            'cpf_cnpj' => '',
            'endereco' => '',
            'email_contato' => '',
        ];
    }

    public function normalize(array $input)
    {
        return [
            'nome_cliente' => $this->capitalizeName($input['nome_cliente'] ?? ''),
            'telefone_contato' => trim((string) ($input['telefone_contato'] ?? '')),
            'cpf_cnpj' => $this->nullableDigits($input['cpf_cnpj'] ?? ''),
            'endereco' => $this->nullableTrim($input['endereco'] ?? ''),
            'email_contato' => $this->nullableTrim($input['email_contato'] ?? ''),
        ];
    }

    public function validate(array $cliente)
    {
        if ($cliente['nome_cliente'] === '') {
            return AlertRenderer::make('danger', 'O nome do cliente é obrigatório.');
        }

        if ($cliente['telefone_contato'] === '') {
            return AlertRenderer::make('danger', 'O telefone de contato é obrigatório.');
        }

        if ($cliente['cpf_cnpj'] !== null && !in_array(strlen($cliente['cpf_cnpj']), [11, 14], true)) {
            return AlertRenderer::make('danger', 'Se informado, CPF/CNPJ deve ter 11 ou 14 dígitos.');
        }

        if ($cliente['email_contato'] !== null && !filter_var($cliente['email_contato'], FILTER_VALIDATE_EMAIL)) {
            return AlertRenderer::make('danger', 'Informe um e-mail válido.');
        }

        return null;
    }

    public function create(array $input)
    {
        $cliente = $this->normalize($input);
        $alert = $this->validate($cliente);

        if ($alert) {
            return ['ok' => false, 'alert' => $alert, 'data' => $cliente];
        }

        try {
            $this->repository->create($cliente);
            return [
                'ok' => true,
                'alert' => AlertRenderer::make('success', 'Cliente cadastrado com sucesso!'),
                'data' => $this->defaultData(),
            ];
        } catch (Throwable $e) {
            error_log('Erro ao cadastrar cliente: ' . $e->getMessage());

            return [
                'ok' => false,
                'alert' => AlertRenderer::make('danger', 'Erro ao cadastrar cliente. Tente novamente.'),
                'data' => $cliente,
            ];
        }
    }

    public function update($id, array $input)
    {
        $cliente = $this->normalize($input);
        $alert = $this->validate($cliente);

        if ($alert) {
            return ['ok' => false, 'alert' => $alert, 'data' => $cliente];
        }

        try {
            $this->repository->update($id, $cliente);
            return ['ok' => true, 'alert' => null, 'data' => $cliente];
        } catch (Throwable $e) {
            error_log('Erro ao atualizar cliente: ' . $e->getMessage());

            return [
                'ok' => false,
                'alert' => AlertRenderer::make('danger', 'Erro ao atualizar cliente. Tente novamente.'),
                'data' => $cliente,
            ];
        }
    }

    private function capitalizeName($name)
    {
        $name = trim((string) preg_replace('/\s+/', ' ', (string) $name));

        if ($name === '') {
            return '';
        }

        if (function_exists('mb_convert_case')) {
            return mb_convert_case(mb_strtolower($name, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
        }

        return ucwords(strtolower($name));
    }

    private function nullableDigits($value)
    {
        $digits = preg_replace('/\D+/', '', (string) $value);
        return $digits === '' ? null : $digits;
    }

    private function nullableTrim($value)
    {
        $text = trim((string) $value);
        return $text === '' ? null : $text;
    }
}
