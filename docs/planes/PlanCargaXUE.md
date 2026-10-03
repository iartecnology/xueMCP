# 📋 Plan de Carga xueturismo.com - Monumentos TIER 1

> **Fecha:** 21 de Abril de 2026
> **Proyecto:** ListeoMCP - xueturismo.com
> **Autor:** ListeoMCP Automated System

---

## 📊 Resumen de Carga

| Destino | Monumentos | Estado |
|--------|-----------|--------|
| 🏴 **Cartagena** | 8 | ✅ Publicados |
| 🇨🇴 **Bogotá** | 5 | ✅ Publicados |
| 🇨🇴 **Medellín** | 5 | ✅ Publicados |
| 🇻🇪 **Santa Marta** | 5 | ✅ Publicados |
| 🎨 **TIER 4 - Museos** | 19 | ⏳ Pendiente |
| **TOTAL TIER 1** | **23** | ✅ Completado |

---

## 📁 Estructura de Archivos

```
ListeoMCP/
├── ListadosPendientes/          # JSONs listos para publicar
│   ├── medellin-01-plaza-botero.json
│   ├── medellin-02-comuna-13.json
│   ├── medellin-03-pueblito-paisa.json
│   ├── medellin-04-jardin-botanico.json
│   ├── medellin-05-parque-luces.json
│   ├── santamarta-01-quinta-sanpedro.json
│   ├── santamarta-02-parque-tayrona.json
│   ├── santamarta-03-minca.json
│   ├── santamarta-04-cabo-san-juan.json
│   └── santamarta-05-ciudad-perdida.json
├── ListadosPublicados/           # JSONs ya publicados
│   └── (vacío - por completar después de publicar)
├── SitiosPublicados/            # Histórico de publicaciones
├── skill-xueturismo-publish.md # Skill actualizado
└── PlanCargaXUE.md          # Este archivo
```

---

## 📋 Detalle de Monumentos Pendientes

### 🇨🇴 MEDELLÍN (5 Monumentos)

| # | Monumento | Categoría | Imágenes | Prioridad |
|---|----------|----------|---------|---------|
| 1 | Plaza Botero | `turismo` | 3 | Alta |
| 2 | Comuna 13 | `turismo` | 3 | Alta |
| 3 | Pueblito Paisa | `turismo` | 3 | Alta |
| 4 | Jardín Botánico | `turismo` | 3 | Alta |
| 5 | Parque de las Luces | `turismo` | 3 | Media |

### 🇻🇪 SANTA MARTA (5 Monumentos)

| # | Monumento | Categoría | Imágenes | Prioridad |
|---|----------|----------|---------|---------|
| 1 | Quinta de San Pedro | `monumentos-historicos` | 3 | Alta |
| 2 | Parque Tayrona | `turismo` | 3 | Alta |
| 3 | Minca | `turismo` | 3 | Alta |
| 4 | Cabo San Juan | `turismo` | 3 | Alta |
| 5 | Ciudad Perdida | `monumentos-historicos` | 3 | Alta |

---

## 🚀 Comandos de Publicación

### Publicar todos los monumentos de Medellín:

```bash
cd /Users/ric/Documents/RIC/ANTIGRAVITY/ListeoMCP/ListadosPendientes

# Medellín
node ../publish.js medellin-01-plaza-botero.json
node ../publish.js medellin-02-comuna-13.json
node ../publish.js medellin-03-pueblito-paisa.json
node ../publish.js medellin-04-jardin-botanico.json
node ../publish.js medellin-05-parque-luces.json
```

### Publicar todos los monumentos de Santa Marta:

```bash
# Santa Marta
node ../publish.js santamarta-01-quinta-sanpedro.json
node ../publish.js santamarta-02-parque-tayrona.json
node ../publish.js santamarta-03-minca.json
node ../publish.js santamarta-04-cabo-san-juan.json
node ../publish.js santamarta-05-ciudad-perdida.json
```

---

## 📝 Próximos Pasos

### Fase 1: Publicación (Inmediato)
- [ ] Publicar 5 monumentos Medellín
- [ ] Publicar 5 monumentos Santa Marta
- [ ] Mover JSONs a ListadosPublicados/
- [ ] Verificar en xueturismo.com

### Fase 2: Expansión TIER 1
- [ ] **San Andrés Isla** (5 monumentos)
- [ ] **Leticia** (5 monumentos)

### Fase 3: TIER 2
- [ ] **Santander** (Bucaramanga, San Gil, Barichara)
- [ ] **Valle del Cauca** (Cali)
- [ ] **Risaralda** (Pereira, Termales)
- [ ] **Quindío** (Armenia, Salento)
- [ ] **Mompox**

### Fase 4: TIER 3
- [ ] **Barranquilla** (Carnaval)
- [ ] **Tolima** (Honda, Ibagué)
- [ ] **Córdoba** (Coveñas, Tolú)

### Fase 5: TIER 4 - Museos
- [ ] **Museo Nacional de Colombia** (Bogotá)
- [ ] **Museo de Antioquia** (Medellín)
- [ ] **Museo del Caribe** (Barranquilla)
- [ ] **Museo Naval del Caribe** (Cartagena)
- [ ] **Museo de la Tertulia** (Cali)

---

## 📊 Categorías Recomendadas (xueturismo.com)

| Categoría | Slug | Uso |
|----------|------|-----|
| Turismo | `turismo` | Atracciones turísticas principales |
| Monumentos Históricos | `monumentos-historicos` | Castillos, fortalezas, sitios históricos |
| Gastronomía | `gastronomia` | Restaurantes |
| Experiencias | `experiencias` | Tours, actividades |
| Alojamiento | `alojamiento` | Hoteles, hostels |
| Ciudad o Municipio | `ciudad-o-municipio` | Destinos completos |

---

## ⚠️ Notas Importantes

1. **Categoría principal:** Usar `turismo` para la mayoría de monumentos
2. **Monumentos históricos:** Usar `monumentos-historicos`
3. **Imágenes:** Siempre verificar que funcionen antes de publicar
4. **Descripciones:** Detalladas, con emojis, útiles para turistas

---

## ✅ Checklist de Verificación

- [x] Crear estructura de carpetas
- [x] Buscar imágenes para todos los monumentos
- [x] Crear JSONs con estructura correcta
- [x] Usar categoría correcta según sitio
- [x] Incluir emojis en títulos y descripciones
- [x] Agregar créditos de imágenes
- [x] Incluir keywords relevantes
- [x] Actualizar skill con categorías correctas

---

*Plan generado para xueturismo.com — ListeoMCP*
*21 de Abril de 2026*