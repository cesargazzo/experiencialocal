@php $policy = \App\Support\PasswordPolicy::current(); @endphp
<ul {{ $attributes->merge(['class' => 'requirements']) }}>
  @foreach ($policy->requirements() as $requirement)
    <li><x-icon name="check" :size="14" /> {{ $requirement }}</li>
  @endforeach
</ul>
