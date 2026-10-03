import json
import html

with open('/tmp/all_current_listings.json', 'r', encoding='utf-8') as f:
    listings = json.load(f)

listings.sort(key=lambda x: x['id'], reverse=True)

lines = []
lines.append('# Inventario Maestro de Sitios y Publicaciones — XUÉ Turismo')
lines.append('')
lines.append(f'> **Total de publicaciones en vivo:** {len(listings)}')
lines.append('> **Última sincronización con la base de datos:** Septiembre 2026')
lines.append('> **Propósito:** Registro maestro centralizado para auditoría, control de calidad y prevención de duplicados.')
lines.append('')
lines.append('---')
lines.append('')
lines.append('| ID | Título de la Publicación | Enlace Directo | Categorías |')
lines.append('| :--- | :--- | :--- | :--- |')

for item in listings:
    post_id = item.get('id', '')
    raw_title = item.get('title', {}).get('rendered', '')
    title = html.unescape(raw_title).replace('|', '-').strip()
    link = item.get('link', '')
    categories = item.get('categories', [])
    cats_str = ', '.join(str(c) for c in categories) if categories else 'N/A'
    lines.append(f'| `{post_id}` | {title} | [Ver Listado]({link}) | `{cats_str}` |')

output_text = '\n'.join(lines)

with open('sitios_publicados.md', 'w', encoding='utf-8') as f:
    f.write(output_text)

print(f'Master list updated successfully with {len(listings)} items!')
