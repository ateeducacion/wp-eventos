---
id: ADR-0014
title: "El CSS a medida es del área; el JavaScript, solo de administración"
status: Aceptada
date: 2026-09-13
related:
  issues: []
  prs: []
  sdds: [SDD-0001, SDD-0002]
  adrs: [ADR-0001, ADR-0006, ADR-0009, ADR-0010, ADR-0012, ADR-0013]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5"
---

# ADR-0014: El CSS a medida es del área; el JavaScript, solo de administración

## Estado

Aceptada (2026-09-13).

## Contexto

Los eventos del subsitio no se parecen entre sí a propósito: cada uno tiene su
cabecera, su color, su tipografía y su cartel. El panel «Apariencia» del taller
del evento cubre lo previsible —color de fondo y de texto de la cabecera, dos
tipografías, logo, cartel, forma de las imágenes y separador—, que es
exactamente lo que hoy se rellena a mano en el formulario del sistema que se
sustituye ([SDD-0002](../sdd/SDD-0002-arquitectura-de-reemplazo.md) §2). Lo imprevisible
—una regla para tapar un hueco que deja el tema en un evento concreto, un
retoque que solo hace falta un día— no cabe en una lista cerrada de campos, y
si no hay dónde ponerlo acaba pegado dentro del contenido de la página, que es
donde peor está.

De ahí los dos campos: `evt_custom_css` y `evt_custom_js`, en el evento raíz y
en cada página satélite
([SDD-0002](../sdd/SDD-0002-arquitectura-de-reemplazo.md), «Las metas del
evento»). Lo del raíz viste todas las páginas del evento; lo de una página va
después y solo en ella. Nada sale de las páginas de su evento.

El segundo campo es el problema. **El JavaScript a medida es ejecución de
código arbitrario en el navegador de cada visitante**, con la sesión de quien
esté mirando. Es exactamente el poder que WordPress protege con
`unfiltered_html`, y es exactamente el error que comete hoy **un fragmento de
código pegado a mano** en el sitio real, que retira esa protección para todas
las peticiones y sin condición de formulario, devolviéndosela a quien tenga la capacidad primitiva
en su rol —administración y edición, no todo el mundo—.
Abrir aquí un campo de JavaScript sin permisos propios sería reabrir por la
puerta de al lado ese mismo agujero.

Hay un tercer hecho que condiciona la solución: el aplicativo **se despliega en
un subsitio de un multisitio**. WordPress, en multisitio, le quita
`unfiltered_html` a
quien administra un subsitio y se la reserva a la superadministración de red
(`wp-includes/capabilities.php`, caso `'unfiltered_html'`). No es un descuido:
es la regla que impide que quien manda en un sitio pueda inyectar código en el
navegador de gente de toda la red.

## Problema

¿Quién puede escribir el CSS y el JavaScript a medida de un evento, y cómo se
concede ese permiso sin repetir esa concesión global?

## Factores de decisión

- **El riesgo de los dos campos no es el mismo**, y es la frase que hay que
  poder repetir en una reunión:

  > **El CSS cambia cómo se ve una página. El JavaScript ejecuta código en el
  > navegador de cada visitante.**

  | | CSS a medida | JavaScript a medida |
  |---|---|---|
  | Qué puede hacer como mucho | dejar la página fea, torcida o ilegible | leer la sesión de quien mira, enviar lo que teclea a otro servidor, cambiar adónde va un formulario |
  | A quién alcanza | a quien mira esa página, mientras mira | a quien mira esa página **y a su cuenta**, también después |
  | Cómo se deshace | vaciando el campo; el daño se va con la recarga | vaciando el campo, y además averiguando qué llegó a hacer mientras estuvo puesto |
  | Qué protege WordPress con `unfiltered_html` | nada de esto | exactamente esto |
  | Se puede revisar leyéndolo | sí, aunque sea largo | no de forma fiable: se ofusca, se minifica y puede traerse más código de fuera |

- **La regla es no conceder `unfiltered_html`.** El aplicativo nace sin
  concederla y no puede acabar haciéndolo de rebote.
- **La regla del multisitio es de la plataforma**, y este repositorio no está
  en posición de decidir que la red se equivoca.
- **Los permisos se preguntan por capacidad, nunca por nombre de rol**
  ([ADR-0006](ADR-0006-el-area-es-un-ambito-no-un-rol.md)).
- **Lo que se abre tiene que verse en el repositorio.** Una excepción que se
  marca en un editor de roles no la encuentra nadie seis meses después.
- **Quien mire una pantalla tiene que entender por qué ve lo que ve**, y quien
  no distingue el amarillo, también.
- **El área es quien maqueta.** El panel «Apariencia» cubre lo previsible; lo
  imprevisible —el hueco que deja el tema en un evento concreto, el retoque de
  un día— es exactamente el trabajo del área, y quien organiza la jornada no
  debería abrir un ticket para mover un margen.

## Alternativas consideradas

### Opción 1: una sola capacidad para los dos campos

Un `evt_edit_custom_code` que abre el CSS y el JavaScript a la vez.

- Pros: una fila menos en la tabla de roles; más simple de explicar.
- Contras: iguala dos riesgos que no son iguales. El caso realista es
  precisamente el que no admite: en esta red, quien administra el subsitio
  debería poder retocar el CSS y **no** debería poder inyectar JavaScript. Con
  una sola capacidad hay que elegir entre cerrarle el CSS sin motivo o abrirle
  el JavaScript sin permiso. Descartada.

### Opción 2: los dos campos al área

Conceder el CSS **y el JavaScript** a `evt_organiser`.

- Pros: un área se maqueta el evento entera sin pedir nada a nadie.
- Contras: el `evt_organiser` es un perfil de área, no de plataforma, y lo
  lleva mucha gente, repartida por todas las áreas
  ([roles y permisos](../roles-y-permisos.md) §A). Un campo de JavaScript en
  manos de ese conjunto es una superficie de XSS almacenado tan grande como la
  que abre ese fragmento, solo que con mejor presentación. **Descartada por el
  JavaScript, no por el CSS**: el CSS sí es del área, y eso es lo que decide la
  opción 5.

### Opción 3: los dos campos solo a administración

Ni el CSS ni el JavaScript para el área. Es lo más cerrado y lo que menos hay
que explicar.

- Pros: un solo reparto, una sola frase.
- Contras: **iguala otra vez los dos riesgos**, ahora por el lado prudente, y
  cobra la prudencia donde no hace falta. Un área que se equivoca escribiendo
  CSS rompe su propia página y lo ve al recargar; el daño es visible, local y se
  deshace vaciando el campo. Deja al área pidiendo turno para mover un margen en
  su propio evento, que es exactamente lo que este aplicativo viene a quitar.
  Descartada.

### Opción 4: conceder `unfiltered_html` a quien administra el subsitio

Un `map_meta_cap` que devuelva la capacidad a la administración local para que
el campo de JavaScript funcione en la red.

- Pros: el campo funciona en el subsitio sin más.
- Contras: es literalmente ese fragmento con otro nombre. Rodea una decisión
  de la plataforma en vez de respetarla, y además de abrir el campo abriría
  todo lo demás que cuelga de `unfiltered_html` en el sitio. Descartada sin
  matices.

### Opción 5: dos capacidades, el CSS al área y el JavaScript a administración sujeto a la regla del multisitio, con una válvula explícita — ELEGIDA

- Pros: cada campo con su permiso y cada permiso donde está su riesgo; el área
  maqueta su evento entero sin pedir turno; la regla de la red se respeta tal
  cual; y el caso legítimo en que haga falta abrir el JavaScript en un subsitio
  se resuelve con un `add_filter` que está en el repositorio, se lee en un
  `grep` y se audita.
- Contras: hay que explicar por qué se ve un campo y no el otro, y una válvula,
  por visible que sea, sigue siendo una válvula.

## Decisión

### 1. Dos capacidades, no una

| Capacidad | Abre | A quién se le concede |
|---|---|---|
| `evt_edit_custom_css` | el campo de CSS del evento y el de cada página | a `evt_organiser`, dentro de su área, y a `administrator` |
| `evt_edit_custom_js` | el campo de JavaScript, y además exige la regla de abajo | **solo** a `administrator` |

Las reparte `EventPostType::grant_code_caps()`
(`src/Evt/PostType/EventPostType.php`), fuera del bucle que reparte las
de los tipos de contenido porque estas dos no son de ningún tipo de contenido.
`evt_edit_custom_js` está además en la lista de capacidades que **ningún rol del
aplicativo puede tener nunca** (`snippets/roles-and-profiles.php`,
`evt_forbidden_role_caps()`), que es la raya escrita y comprobada. Son
capacidades propias del aplicativo, como `evt_manage_app` y
`evt_edit_all_areas`: se preguntan por capacidad y no por rol.

Tener la capacidad no basta. `EventAccess::can_edit_custom_css()`
(`src/Evt/Access/EventAccess.php`) exige **las dos cosas**: la capacidad y
poder editar ese evento en concreto, con su acotado por área. Una capacidad
global no abre los eventos de otra área.

### 2. En multisitio, el JavaScript respeta `unfiltered_html`

`EventAccess::can_edit_custom_js()` (`EventAccess.php`) añade una
condición: si el sitio es multisitio, hace falta además `unfiltered_html`.

```php
$suelto = ! is_multisite() || user_can( $user_id, 'unfiltered_html' );
return (bool) apply_filters( 'evt_allow_custom_js', $suelto, $user_id, $post_id );
```

No se rodea la regla de la red: se respeta. En un subsitio eso significa que,
tal cual, el campo de JavaScript lo ve la superadministración de red y no quien
administra solo el subsitio. Es la consecuencia buscada, y es lo **contrario**
de lo que hace hoy ese fragmento, que retira esa misma barrera para todas las
peticiones del sitio.

| | El fragmento de hoy | Este aplicativo |
|---|---|---|
| Qué hace con la barrera del multisitio | la retira | la respeta |
| A quién alcanza | a toda petición del sitio | a un campo, en un evento, para quien tenga las dos capacidades |
| Cómo se abre una excepción | ya está abierta para todos, en silencio | un `add_filter( 'evt_allow_custom_js', … )` en el repositorio |
| Dónde se ve | en un snippet de la base de datos de producción | en `src/Evt/Access/EventAccess.php` y en el fichero que ponga el filtro |

### 3. La válvula es un filtro visible, no una concesión global

Para el caso legítimo —una red donde la administración del subsitio sí es de
confianza para esto— se deja `evt_allow_custom_js`, documentado en el propio
código. Se eligió un filtro y no una constante, una opción o una casilla en
«Ajustes» por una razón concreta: **un filtro solo existe si alguien escribe la
línea que lo añade, y esa línea vive en un fichero versionado**. Una casilla se
marca un martes, no deja rastro de quién ni por qué, y no aparece en ninguna
revisión de código ([ADR-0012](ADR-0012-politica-de-edicion-y-auditoria.md)).

El filtro **solo puede relajar la regla del multisitio**. La capacidad
`evt_edit_custom_js` y el acotado por área se comprueban antes y el filtro no
los toca: quien no puede editar el evento sigue sin poder.

### 4. La puerta se cierra en los tres sitios, no solo en la pantalla

- La pestaña **«Código»** del taller **no existe** para quien no tiene ninguna
  de las dos capacidades: `EventWorkspace::panels()` no la añade
  (`src/Evt/PublicFront/EventWorkspace.php`). Esconder el contenido y
  dejar la pestaña sería enseñar una puerta cerrada.
- El **manejador del POST** vuelve a comprobar capacidad por capacidad antes de
  guardar cada meta (`EventWorkspace.php`,
  `src/Evt/PublicFront/PageForm.php`): quien fabrique el envío no
  guarda nada.
- El **`auth_callback`** de las dos metas pregunta lo mismo
  (`auth_custom_css()` y `auth_custom_js()`), así que tampoco se
  escriben por REST ni por ningún otro camino.

Quien tiene el CSS pero no el JavaScript —el caso normal de un área— **no ve un
hueco**: ve dicho, en castellano y en una línea, qué falta y por qué
(`src/Evt/PublicFront/View/EventCodePanel.php`, `missing_js()`). Un hueco
callado se lee como una avería.

### 5. La convención del recuadro amarillo

Esto es una decisión de interfaz y se escribe aquí porque nace con estos campos
pero **no es de estos campos**: es la convención del aplicativo para todo lo que
solo ve quien administra. Aquí envuelve **el JavaScript y nada más**. El CSS es
un campo normal de la pestaña «Código», con su ayuda en castellano llano: si el
amarillo envolviera también algo que el área ve, la convención dejaría de
significar lo que dice.

Se implementa **una vez**, en el armazón: `Shell::admin_box( $titulo, $cuerpo,
$explicacion )` (`src/Evt/PublicFront/Shell.php`) y la clase
`.evt-solo-admin` (`assets/css/evt-app.css`). Cualquier pantalla futura
la reutiliza; nadie vuelve a pintar un recuadro amarillo a mano.

Lleva tres cosas, y las tres son obligatorias:

1. **fondo amarillo suave y borde amarillo marcado**, con el borde izquierdo
   engrosado para que se reconozca de reojo;
2. **la etiqueta «Solo administración» escrita**, en una insignia con un icono
   de escudo, en la esquina superior;
3. **una línea que explica por qué lo ve solo esa persona**, distinta para el
   CSS y para el JavaScript, porque el motivo no es el mismo.

**El color no puede ser el único indicador.** Quien no distingue el amarillo se
quedaría sin el aviso entero, así que el aviso va **en texto**: la etiqueta
«Solo administración» es una cadena, no un icono suelto ni un borde de color, y
el porqué está escrito debajo. El icono lleva `aria-hidden="true"` porque no
aporta nada que el texto no diga ya.

**Contraste, comprobado y no supuesto** (fórmula WCAG 2.1 sobre los tokens de
`assets/css/evt-app.css`):

| Pareja | Ratio | Umbral | |
|---|---:|---|---|
| Texto `#4a3a05` sobre fondo `#fdf6dd` | **10,22:1** | 4,5:1 (AA texto normal) | cumple, y AAA |
| Etiqueta: blanco sobre `#6b5200` | **7,42:1** | 4,5:1 | cumple, y AAA |
| Borde `#a67c00` sobre el fondo del recuadro | **3,52:1** | 3:1 (AA elementos no textuales) | cumple |

Dentro del recuadro, `label`, `small`, `p` y `legend` vuelven a fijar el color
oscuro (las reglas de `.evt-solo-admin`): si un rótulo heredara el gris de fuera se
caería por debajo del 4,5:1 sin que nadie se diera cuenta.

## Consecuencias

### Positivas

- **El aplicativo gana el campo de escape que le faltaba** sin abrir la mano en
  permisos: lo imprevisible tiene dónde ir y deja de pegarse dentro del
  contenido de la página.
- **La regla sigue en pie.** No se concede `unfiltered_html` en ningún sitio,
  ni directa ni indirectamente; la barrera del multisitio se respeta.
- **Dos riesgos distintos, dos permisos distintos.** Se da el CSS y no el
  JavaScript, que es justo el reparto que hace falta aquí.
- **Un área maqueta su evento entero sin pedir turno**, que es la razón de ser
  del aplicativo.
- **El recuadro amarillo significa una sola cosa.** Envuelve exactamente lo que
  reserva; una convención que envuelve dos campos y uno de ellos no lo es se
  degrada sola.
- **La excepción se ve.** Abrir el JavaScript en un subsitio exige una línea en
  un fichero versionado, con su revisión y su historia, en vez de una casilla
  sin rastro.
- **El recuadro amarillo es una pieza del armazón**, con su contraste medido, y
  no una decoración que se copie y se degrade pantalla a pantalla.
- **La comprobación está en tres capas** —la pestaña, el POST y el
  `auth_callback`—, así que ninguna de ellas es la única que protege.

### Negativas

- **El CSS a medida puede dejar un evento ilegible**, y ahora eso lo puede hacer
  más gente. Se acepta: el daño es visible, es local a las páginas de ese evento
  y se deshace vaciando el campo. El saneado que impide cerrar la etiqueta
  `</style` sigue en su sitio.
- **Hay que explicar por qué se ve un campo y no el otro**, y esa explicación es
  de las que no se leen. Por eso va en la propia pantalla y en una línea, no en
  un enlace a esta ADR.
- **En este subsitio, el campo de JavaScript probablemente no lo verá nadie de
  los que trabajan aquí a diario**, porque `unfiltered_html` es de la
  superadministración de red. Puede resultar desconcertante: por eso la
  pantalla lo explica en vez de callar.
- **La válvula existe.** `evt_allow_custom_js` puede usarse mal, y un
  `add_filter( 'evt_allow_custom_js', '__return_true' )` deja el campo abierto a
  todo el que tenga la capacidad. Es visible y auditable, que es lo mejor que se
  puede pedir de una válvula, pero no es lo mismo que no tenerla.
- **El JavaScript guardado se sigue ejecutando aunque a quien lo escribió se le
  retire el permiso.** Se dice en la propia pantalla («el que ya hubiera
  guardado sigue ejecutándose tal cual»), pero conviene tenerlo presente: quitar
  la capacidad no limpia lo que ya hay.
- **Dos capacidades más que mantener** en la tabla de roles, en WPFront y en
  esta documentación.

### Neutras

- El saneado es mínimo y a propósito: el CSS pierde las etiquetas y no puede
  cerrar su `</style` (`EventMetaRegistration::sanitize_custom_css()`), y al
  JavaScript solo se le desactiva la fuga por `</script`
  (`sanitize_custom_js()`). Escapar el código lo rompería; el filtro de verdad
  es el permiso, no el saneado.
- El editor de esos dos campos es el del núcleo, con sus comprobadores de
  errores: [ADR-0013](ADR-0013-el-editor-de-codigo-es-el-de-wordpress.md).
- Las capacidades son inertes mientras nadie las conceda: si el snippet de roles
  no se ha activado, ni siquiera el `administrator` ve la pestaña. Es el
  fail-closed de siempre.
