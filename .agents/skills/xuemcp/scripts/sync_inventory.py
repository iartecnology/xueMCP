#!/usr/bin/env python3
"""
sync_inventory.py
Sincroniza y actualiza el archivo maestro sitios_publicados.md consultando
la API REST de WordPress (xueturismo.com).
"""
import sys
import json
import urllib.request
import urllib.error
from datetime import datetime

API_BASE = "https://xueturismo.com/wp-json/wp/v2/listing"
HEADERS = {"User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36"}

def fetch_all_listings():
    listings = []
    page = 1
    per_page = 100

    print(f"[*] Consultando API de xueturismo.com...")
    while True:
        url = f"{API_BASE}?per_page={per_page}&page={page}&_fields=id,title,link,listing_category"
        req = urllib.request.Request(url, headers=HEADERS)
        try:
            with urllib.request.urlopen(req, timeout=15) as res:
                total = res.headers.get("X-WP-Total")
                total_pages = int(res.headers.get("X-WP-TotalPages", 1))
                data = json.loads(res.read().decode("utf-8"))
                if not data:
                    break
                listings.extend(data)
                print(f" -> Página {page}/{total_pages} obtenida ({len(listings)}/{total} items)...")
                if page >= total_pages:
                    break
                page += 1
        except urllib.error.HTTPError as e:
            if e.code == 400: # Fuera de páginas
                break
            print(f"[!] Error HTTP {e.code} en página {page}: {e.reason}", file=sys.stderr)
            break
        except Exception as e:
            print(f"[!] Error inesperado en página {page}: {e}", file=sys.stderr)
            break

    return listings

def update_inventory_md(listings, output_path="sitios_publicados.md"):
    total = len(listings)
    now_str = datetime.now().strftime("%Y-%m-%d %H:%M")
    
    lines = [
        "# Inventario Maestro de Sitios y Publicaciones — XUÉ Turismo\n\n",
        f"> **Total de publicaciones en vivo:** {total}\n",
        f"> **Última sincronización con la base de datos:** {now_str}\n",
        "> **Propósito:** Registro maestro centralizado para auditoría, control de calidad, actualización incremental y prevención de duplicados.\n\n",
        "---\n\n",
        "| ID | Título de la Publicación | Enlace Directo | Categorías |\n",
        "| :--- | :--- | :--- | :--- |\n"
    ]

    for item in listings:
        lid = item.get("id")
        title = item.get("title", {}).get("rendered", "Sin Título")
        # Limpiar caracteres conflictivos de markdown table
        clean_title = title.replace("|", "-").strip()
        link = item.get("link", "")
        cats = item.get("listing_category", [])
        cats_str = ", ".join(map(str, cats)) if cats else "N/A"
        
        lines.append(f"| `{lid}` | {clean_title} | [Ver Listado]({link}) | `{cats_str}` |\n")

    with open(output_path, "w", encoding="utf-8") as f:
        f.writelines(lines)

    print(f"[✓] Inventario actualizado exitosamente con {total} publicaciones en: {output_path}")

if __name__ == "__main__":
    items = fetch_all_listings()
    if items:
        update_inventory_md(items)
    else:
        print("[!] No se pudieron obtener listados de la API.", file=sys.stderr)
