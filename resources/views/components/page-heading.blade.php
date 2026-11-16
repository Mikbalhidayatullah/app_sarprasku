@props(['title','eyebrow'=>'RUANG KERJA','description'=>null])
<div class="page-heading"><div><div class="eyebrow">{{ $eyebrow }}</div><h1>{{ $title }}</h1>@if($description)<p>{{ $description }}</p>@endif</div><div class="heading-actions">{{ $slot }}</div></div>
