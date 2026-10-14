{{-- El logo llega como PNG desde public/, no como SVG en línea: es una imagen
     con canal alfa, así que se ve igual sobre la barra clara y la oscura. --}}
<img src="{{ asset('logo.png') }}" alt="{{ config('app.name') }}" {{ $attributes }}>
