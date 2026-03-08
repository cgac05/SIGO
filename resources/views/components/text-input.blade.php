@props(['disabled' => false])

<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'border-gray-300 focus:border-blue-600 focus:ring-blue-600 rounded-md shadow-sm']) !!}>
<!-- Cammbio de color de borde y foco a azul para que combine con el tema de la aplicación -->