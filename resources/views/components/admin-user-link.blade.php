@props(['user', 'label' => null, 'withEmail' => false])
{{-- Quien puede ver usuarios llega a la ficha; el resto ve solo el nombre de pila y el número de cuenta. --}}
@if (auth()->user()->can('team.users.view'))
  <a href="{{ route('admin.usuarios.show', $user) }}">{{ $label ?? $user->name }}</a>@if ($withEmail) ({{ $user->email }})@endif
@else
  <span>{{ $user->first_name }} <small class="hint">· cuenta #{{ $user->id }}</small></span>
@endif
