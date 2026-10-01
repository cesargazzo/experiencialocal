<?php

/*
|--------------------------------------------------------------------------
| Mensajes de validación en portugués de Brasil
|--------------------------------------------------------------------------
|
| Las reglas que más usan las pantallas del viajero. Lo que no está acá
| sale del archivo en inglés (fallback_locale).
|
*/

return [
    'accepted' => 'Você precisa aceitar :attribute.',
    'after' => 'O campo :attribute deve ser uma data posterior a :date.',
    'after_or_equal' => 'O campo :attribute deve ser uma data igual ou posterior a :date.',
    'array' => 'O campo :attribute deve ser uma lista.',
    'before' => 'O campo :attribute deve ser uma data anterior a :date.',
    'before_or_equal' => 'O campo :attribute deve ser uma data igual ou anterior a :date.',
    'between' => [
        'numeric' => 'O campo :attribute deve estar entre :min e :max.',
        'string' => 'O campo :attribute deve ter entre :min e :max caracteres.',
    ],
    'boolean' => 'O campo :attribute deve ser verdadeiro ou falso.',
    'confirmed' => 'A confirmação de :attribute não confere.',
    'current_password' => 'A senha está incorreta.',
    'date' => 'O campo :attribute deve ser uma data válida.',
    'date_format' => 'O campo :attribute deve estar no formato :format.',
    'email' => 'O campo :attribute deve ser um e-mail válido.',
    'exists' => 'O :attribute selecionado é inválido.',
    'file' => 'O campo :attribute deve ser um arquivo.',
    'image' => 'O campo :attribute deve ser uma imagem.',
    'in' => 'O :attribute selecionado é inválido.',
    'integer' => 'O campo :attribute deve ser um número inteiro.',
    'max' => [
        'array' => 'O campo :attribute pode ter no máximo :max itens.',
        'file' => 'O arquivo :attribute pode ter no máximo :max kilobytes.',
        'numeric' => 'O campo :attribute não pode ser maior que :max.',
        'string' => 'O campo :attribute pode ter no máximo :max caracteres.',
    ],
    'mimes' => 'O campo :attribute deve ser um arquivo do tipo: :values.',
    'min' => [
        'array' => 'O campo :attribute deve ter pelo menos :min itens.',
        'file' => 'O arquivo :attribute deve ter pelo menos :min kilobytes.',
        'numeric' => 'O campo :attribute deve ser pelo menos :min.',
        'string' => 'O campo :attribute deve ter pelo menos :min caracteres.',
    ],
    'numeric' => 'O campo :attribute deve ser um número.',
    'password' => [
        'letters' => 'A :attribute deve conter pelo menos uma letra.',
        'mixed' => 'A :attribute deve conter pelo menos uma letra maiúscula e uma minúscula.',
        'numbers' => 'A :attribute deve conter pelo menos um número.',
        'symbols' => 'A :attribute deve conter pelo menos um símbolo.',
        'uncompromised' => 'Esta :attribute apareceu em um vazamento de dados. Escolha outra.',
    ],
    'regex' => 'O formato de :attribute é inválido.',
    'required' => 'O campo :attribute é obrigatório.',
    'required_if' => 'O campo :attribute é obrigatório quando :other é :value.',
    'same' => 'Os campos :attribute e :other devem ser iguais.',
    'string' => 'O campo :attribute deve ser um texto.',
    'unique' => 'Este :attribute já está em uso.',
    'uploaded' => 'Não conseguimos enviar :attribute. Tente de novo.',

    'attributes' => [
        'name' => 'nome completo',
        'first_name' => 'nome',
        'last_name' => 'sobrenome',
        'email' => 'e-mail',
        'phone' => 'telefone',
        'birth_date' => 'data de nascimento',
        'password' => 'senha',
        'password_confirmation' => 'confirmação da senha',
        'current_password' => 'senha atual',
        'nationality_code' => 'nacionalidade',
        'country_code' => 'país',
        'province_id' => 'província',
        'city' => 'cidade',
        'postal_code' => 'CEP',
        'dateId' => 'data',
        'guests' => 'número de pessoas',
        'body' => 'mensagem',
        'code' => 'código',
    ],
];
