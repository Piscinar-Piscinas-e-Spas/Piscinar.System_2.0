<?php

namespace App\Services;

use App\Repositories\FornecedorRepository;
use App\Views\AlertRenderer;

class FornecedorService
{
    private $repository;

    public function __construct(FornecedorRepository $repository)
    {
        $this->repository = $repository;
    }

    public function defaultData(): array
    {
        return [
            'nome_fornecedor' => '',
            'documento' => '',
            'telefone' => '',
            'email' => '',
            'ativo' => 1,
        ];
    }

    public function normalize(array $input): array
    {
        return [
            'nome_fornecedor' => $this->normalizeName($input['nome_fornecedor'] ?? ''),
            'documento' => $this->nullableDigits($input['documento'] ?? ''),
            'telefone' => $this->nullableTrim($input['telefone'] ?? ''),
            'email' => $this->nullableTrim($input['email'] ?? ''),
            'ativo' => 1,
        ];
    }

    public function validate(array $fornecedor): ?array
    {
        if ($fornecedor['nome_fornecedor'] === '') {
            return AlertRenderer::make('danger', 'O nome do fornecedor e obrigatorio.');
        }

        if ($fornecedor['documento'] !== null && !in_array(strlen($fornecedor['documento']), [11, 14], true)) {
            return AlertRenderer::make('danger', 'Se informado, o documento deve ter 11 ou 14 digitos.');
        }

        if ($fornecedor['email'] !== null && !filter_var($fornecedor['email'], FILTER_VALIDATE_EMAIL)) {
            return AlertRenderer::make('danger', 'Informe um e-mail valido para o fornecedor.');
        }

        return null;
    }

    public function create(array $input): array
    {
        $fornecedor = $this->normalize($input);
        $alert = $this->validate($fornecedor);

        if ($alert) {
            return ['ok' => false, 'alert' => $alert, 'data' => $fornecedor];
        }

        try {
            $this->repository->create($fornecedor);
            return [
                'ok' => true,
                'alert' => AlertRenderer::make('success', 'Fornecedor cadastrado com sucesso!'),
                'data' => $this->defaultData(),
            ];
        } catch (\Throwable $e) {
            error_log('Erro ao cadastrar fornecedor: ' . $e->getMessage());

            return [
                'ok' => false,
                'alert' => AlertRenderer::make('danger', 'Erro ao cadastrar fornecedor. Tente novamente.'),
                'data' => $fornecedor,
            ];
        }
    }

    public function update(int $fornecedorId, array $input): array
    {
        $fornecedor = $this->normalize($input);
        $alert = $this->validate($fornecedor);

        if ($alert) {
            return ['ok' => false, 'alert' => $alert, 'data' => $fornecedor];
        }

        try {
            $this->repository->update($fornecedorId, $fornecedor);
            return ['ok' => true, 'alert' => null, 'data' => $fornecedor];
        } catch (\Throwable $e) {
            error_log('Erro ao atualizar fornecedor: ' . $e->getMessage());

            return [
                'ok' => false,
                'alert' => AlertRenderer::make('danger', 'Erro ao atualizar fornecedor. Tente novamente.'),
                'data' => $fornecedor,
            ];
        }
    }

    private function normalizeName($value): string
    {
        $name = trim((string) preg_replace('/\s+/', ' ', (string) $value));

        if ($name === '') {
            return '';
        }

        if (function_exists('mb_convert_case')) {
            return mb_convert_case(mb_strtolower($name, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
        }

        return ucwords(strtolower($name));
    }

    private function nullableDigits($value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value);
        return $digits === '' ? null : $digits;
    }

    private function nullableTrim($value): ?string
    {
        $text = trim((string) $value);
        return $text === '' ? null : $text;
    }
}
