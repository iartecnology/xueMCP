import urllib.request
import json
import sys

content_html = """<p>La capital de Boyacá fue testigo de una verdadera cumbre de identidad y orgullo rural con la realización de <strong>CosechArte 2026</strong>, la Feria de las Artes y la Cosecha que llegó a su tercera edición consolidándose como el corazón productivo, artesanal y culinario del <strong>Festival Internacional de la Cultura Campesina (FICC)</strong> en su versión 53.</p>

<p>Desarrollada del <strong>8 al 12 de octubre de 2026</strong> en el Parque de Eventos contiguo al Estadio La Independencia de Tunja, esta feria de acceso 100% libre transformó la relación entre el campo y la ciudad, atrayendo a más de <strong>80.000 visitantes</strong> y registrando un récord histórico de ventas directas superior a los <strong>$500 millones de pesos</strong> en beneficio de campesinos, artesanos y pequeñas unidades productivas de las 13 provincias del departamento.</p>

<hr />

<h3>🎶 Apertura de Gala con la Banda Sinfónica Nacional</h3>
<p>La inauguración oficial de CosechArte 2026 contó con un despliegue sinfónico de primer nivel gracias a la presentación especial de la <strong>Banda Sinfónica Nacional de Colombia</strong>, dirigida por el maestro Camilo Malagón Tenza. Con una interpretación magistral de bambucos, rumbas criollas y pasillos en formato orquestal, se dio apertura a cinco jornadas continuas de conciertos de carranga, danzas folclóricas, agrupaciones de música campesina y expresiones juveniles de cultura urbana.</p>

<hr />

<h3>🍲 'SumercChef' y 'El Bitute': El Rescate Culinario de los Fogones Ancestrales</h3>
<p>La gastronomía fue uno de los mayores polos de atracción de la feria gracias a dos grandes apuestas:</p>
<ul>
  <li>🏆 <strong>Concurso SumercChef:</strong> Un certamen que convocó a 15 sabedoras y sabedores tradicionales de diversos rincones de Boyacá, compitiendo en vivo con platos que rescatan la memoria agrícola de la región: el emblemático <em>cocido boyacense</em>, la <em>huatia</em> horneada con terrones de tierra, la <em>sopa de ruyas</em>, la <em>arepa liuda</em>, los <em>empedrados</em>, el <em>mute de cebada</em>, el <em>fiambre campesino envuelto en hoja</em> y la tradicional <em>chucula taifa</em>.</li>
  <li>🍲 <strong>Plazoleta El Bitute:</strong> Espacio de cocina viva y maridaje donde las familias degustaron caldos de papa nativa, costillas al horno de leña, amasijos de maíz porva y dulces tradicionales en almíbar elaborados sin conservantes.</li>
</ul>

<hr />

<h3>🧶 Pabellones Temáticos: Artesanías, Bienestar y Familia</h3>
<p>El diseño ferial de CosechArte 2026 ofreció una experiencia inmersiva para cada integrante de la familia:</p>
<ul>
  <li>🧶 <strong>Manos Maestras y Mercado Campesino:</strong> Exhibición de piezas elaboradas por artesanos de los 123 municipios de Boyacá, destacando la cestería en esparto y chin de Cerinza y Tenza, las ruanas en telar de lana virgen de Nobsa, la cerámica ancestral de Ráquira y frutos frescos traídos directamente de la huerta sin intermediación comercial.</li>
  <li>🧒 <strong>La Cosechita:</strong> Escenario pedagógico e interactivo para la niñez con granja de especies menores, talleres de siembra, juegos tradicionales de campo y una exhibición temática de dinosaurios en alianza con el parque <em>Gondava</em> de Sachica.</li>
  <li>🧘 <strong>Alma en Calma:</strong> Espacio de bienestar y medicina preventiva enfocado en el autocuidado, nutrición consciente y salud emocional, dedicado de manera prioritaria a mujeres cuidadoras, lideresas rurales y campesinas.</li>
</ul>

<hr />

<h3>🚀 Rumbo a 2027: Boyacá Extenderá la Feria a 10 Días</h3>
<p>El contundente respaldo del público y la reactivación económica inmediata para los productores locales motivaron un anuncio trascendental por parte del Gobernador de Boyacá, <strong>Carlos Andrés Amaya</strong>: a partir de 2027, CosechArte ampliará su calendario oficial a <strong>diez días consecutivos de programación</strong>, cubriendo dos fines de semana enteros. Esta decisión estratégica busca afianzar a Tunja como el epicentro ferial de turismo cultural más relevante de los Andes colombianos durante el mes de octubre.</p>

<hr />

<h3>💡 Guía y Resumen para el Viajero</h3>
<ul>
  <li>📍 <strong>Ubicación:</strong> Parque de Eventos (contiguo al Estadio La Independencia), Tunja, Boyacá.</li>
  <li>🎟️ <strong>Modalidad de entrada:</strong> Acceso libre y gratuito para todo público.</li>
  <li>🛒 <strong>Impacto directo:</strong> Espacio ideal para adquirir artesanías con denominación de origen y apoyar la economía de familias campesinas boyacenses.</li>
  <li>📅 <strong>Próxima cita:</strong> Octubre de 2027 (con formato extendido de 10 días).</li>
</ul>"""

payload = {
    "ability": "wp/create-post",
    "args": {
        "title": "🌾 CosechArte 2026 en Tunja: El Triunfo de la Tradición, el Emprendimiento y la Identidad Campesina en Boyacá",
        "content": content_html,
        "status": "publish",
        "post_type": "post",
        "categories": [107],
        "image_url": "https://caracol.com.co/resizer/v2/AZKZPNDTQJGTTE5BKFLMFFEVJY.jpeg?quality=70&width=1200&height=900&smart=true"
    }
}

req = urllib.request.Request(
    "https://xueturismo.com/wp-json/mcp-listeo/v1/call",
    data=json.dumps(payload).encode("utf-8"),
    headers={
        "Content-Type": "application/json",
        "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64)",
        "X-MCP-Token": "bgfC0CePAHvI2KbEUIbDe6l5VR9UEwXK"
    }
)

try:
    with urllib.request.urlopen(req, timeout=30) as res:
        print("Status HTTP:", res.status)
        result = json.loads(res.read().decode())
        print("Resultado:", json.dumps(result, indent=2))
except urllib.error.HTTPError as e:
    print("HTTPError:", e.code, e.reason, e.read().decode(), file=sys.stderr)
    sys.exit(1)
except Exception as e:
    print("Error:", e, file=sys.stderr)
    sys.exit(1)
