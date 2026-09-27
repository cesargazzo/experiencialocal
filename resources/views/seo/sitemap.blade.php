{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>{{ route('home') }}</loc>
    <lastmod>{{ $lastModified->toAtomString() }}</lastmod>
    <changefreq>daily</changefreq>
    <priority>1.0</priority>
  </url>
@foreach ($categories as $slug)
  <url>
    <loc>{{ route('home', ['cat' => $slug]) }}</loc>
    <changefreq>daily</changefreq>
    <priority>0.7</priority>
  </url>
@endforeach
@foreach ($experiences as $experience)
  <url>
    <loc>{{ route('experiencias.show', $experience) }}</loc>
    <lastmod>{{ $experience->updated_at->toAtomString() }}</lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
  </url>
@endforeach
</urlset>
