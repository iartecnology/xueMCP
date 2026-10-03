#!/usr/bin/env python3
"""
Image Search Engine for Listings & Tourism Publications.
Uses multi-engine retrieval:
  1. Web Image Index (Google/Bing/DDG CDN direct images) with proper headers & referer.
  2. Wikimedia Commons API for high-resolution verified open media.
"""

import sys
import os
import re
import json
import urllib.request
import urllib.parse

HEADERS = {
    "User-Agent": "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36",
    "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8",
    "Accept-Language": "es-ES,es;q=0.9,en;q=0.8"
}

def search_images(query, limit=5):
    results = []

    # 1. Primary Engine: Web Image Search
    try:
        url = f"https://duckduckgo.com/?q={urllib.parse.quote(query)}"
        req = urllib.request.Request(url, headers=HEADERS)
        html = urllib.request.urlopen(req, timeout=10).read().decode("utf-8", errors="ignore")
        vqd_match = re.search(r'vqd=([\d-]+)', html) or re.search(r'vqd=\"([^\"]+)\"', html)
        if vqd_match:
            vqd = vqd_match.group(1)
            api_url = f"https://duckduckgo.com/i.js?l=es-es&o=json&q={urllib.parse.quote(query)}&vqd={vqd}&f=,,,&p=1"
            img_headers = dict(HEADERS)
            img_headers["Referer"] = "https://duckduckgo.com/"
            img_headers["Accept"] = "application/json, text/javascript, */*; q=0.01"
            req_img = urllib.request.Request(api_url, headers=img_headers)
            raw = urllib.request.urlopen(req_img, timeout=10).read().decode("utf-8")
            data = json.loads(raw)
            for item in data.get("results", []):
                img_url = item.get("image")
                if not img_url:
                    continue
                results.append({
                    "title": item.get("title", ""),
                    "image_url": img_url,
                    "thumbnail": item.get("thumbnail"),
                    "source": item.get("url"),
                    "width": item.get("width"),
                    "height": item.get("height"),
                    "engine": "web_image_index"
                })
                if len(results) >= limit:
                    break
    except Exception as e:
        sys.stderr.write(f"[Warning] Web image search error: {e}\n")

    # 2. Secondary Engine: Wikimedia Commons API
    if len(results) < limit:
        try:
            wiki_url = f"https://commons.wikimedia.org/w/api.php?action=query&generator=search&gsrnamespace=6&gsrsearch={urllib.parse.quote(query)}&gsrlimit=10&prop=imageinfo&iiprop=url|size|mime&format=json"
            req_wiki = urllib.request.Request(wiki_url, headers={
                "User-Agent": "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36"
            })
            wiki_data = json.loads(urllib.request.urlopen(req_wiki, timeout=10).read().decode("utf-8"))
            pages = wiki_data.get("query", {}).get("pages", {})
            for pid, page in pages.items():
                if "imageinfo" in page and page["imageinfo"]:
                    info = page["imageinfo"][0]
                    u = info.get("url")
                    if u and any(u.lower().endswith(ext) for ext in [".jpg", ".jpeg", ".png", ".webp"]):
                        results.append({
                            "title": page.get("title", "").replace("File:", ""),
                            "image_url": u,
                            "thumbnail": u,
                            "source": "https://commons.wikimedia.org/wiki/" + urllib.parse.quote(page.get("title", "")),
                            "width": info.get("width"),
                            "height": info.get("height"),
                            "engine": "wikimedia_commons"
                        })
                        if len(results) >= limit:
                            break
        except Exception as e:
            sys.stderr.write(f"[Warning] Wikimedia Commons error: {e}\n")

    return results

def download_image(image_url, dest_path):
    req = urllib.request.Request(image_url, headers=HEADERS)
    with urllib.request.urlopen(req, timeout=15) as res:
        data = res.read()
        with open(dest_path, "wb") as f:
            f.write(data)
    return len(data)

def main():
    if len(sys.argv) < 2:
        print("Usage: search_images.py <query> [--limit N] [--download <dir>]")
        sys.exit(1)

    query = sys.argv[1]
    limit = 5
    download_dir = None

    i = 2
    while i < len(sys.argv):
        if sys.argv[i] == "--limit" and i + 1 < len(sys.argv):
            limit = int(sys.argv[i + 1])
            i += 2
        elif sys.argv[i] == "--download" and i + 1 < len(sys.argv):
            download_dir = sys.argv[i + 1]
            i += 2
        else:
            i += 1

    results = search_images(query, limit=limit)

    if download_dir:
        os.makedirs(download_dir, exist_ok=True)
        for idx, item in enumerate(results, 1):
            ext = ".jpg"
            clean_url = item["image_url"].split("?")[0]
            if clean_url.endswith(".png"): ext = ".png"
            elif clean_url.endswith(".webp"): ext = ".webp"
            
            clean_name = re.sub(r'[^a-zA-Z0-9_-]', '_', item["title"][:40]).strip('_') or f"image_{idx}"
            dest = os.path.join(download_dir, f"{clean_name}{ext}")
            try:
                size = download_image(item["image_url"], dest)
                item["local_path"] = dest
                item["download_size_bytes"] = size
            except Exception as e:
                item["download_error"] = str(e)

    print(json.dumps(results, indent=2, ensure_ascii=False))

if __name__ == "__main__":
    main()
