# Revisión inicial – 17/11/2026

**Alumno:** Fernando Acosta Algarañaz
**Proyecto:** Desarrollo de un sistema web para la gestión operativa y administrativa de citas,
servicios y cobros en la barbería Blackhound
**Tutor:** Ing. David Serrudo
**Revisado:** el documento Word y los tres diagramas del repositorio (un commit, sin código).

Responder debajo de cada observación: **Corregido** (dónde) o **No corregido** (por qué).

## Resumen

El capítulo I y el capítulo II están redactados y bien encadenados: la matriz de
operacionalización, la línea base y la tabla que une cada variable con su teoría son el punto
fuerte del trabajo. Lo que falta es todo lo que demuestra que el proyecto se está ejecutando:
el diagnóstico aplicado, el capítulo III y el código. Además, los diagramas describen un sistema
distinto del que describe el documento.

## A. Observaciones que cambian el proyecto (resolver primero)

**A1. Los diagramas incluyen inventario y el documento no.**
El modelo entidad-relación y el diagrama de clases tienen `proveedores`, `productos`,
`servicio_producto` y `movimientos_stock`, y la arquitectura tiene un `StockService`. El
documento no menciona inventario en el título, los objetivos, el alcance ni la matriz. Hay que
decidir: o el inventario entra al alcance (y se agrega a objetivos, matriz y cronograma) o sale
de los diagramas.

**A2. El título dice "sistema web" y la arquitectura no tiene interfaz web.**
El diagrama de arquitectura muestra solo una API consumida por "curl / Postman / App móvil". No
aparece el frontend con el que el cliente reserva y el barbero cobra. Definir con qué se
construye la interfaz y agregarla al diagrama.

**A3. La validación con datos simulados no permite medir lo que promete el objetivo 4.**
El documento dice que se valida en un servidor local, con datos simulados y sin producción. Con
eso no se puede medir la reducción de inasistencias ni del tiempo de agendamiento frente a la
línea base, ni aplicar el cuestionario de usabilidad a clientes reales. Además, el diseño se
declara transversal (una sola medición), pero comparar contra la línea base es medir antes y
después. Opciones: hacer un piloto corto en la barbería con citas reales, o reducir los
indicadores a los que sí se pueden medir en laboratorio y decirlo en el alcance.

**A4. Los recordatorios por WhatsApp están simulados.**
El documento presenta el recordatorio por WhatsApp como uno de los cuatro requisitos que
justifican el desarrollo a medida; la arquitectura dice "WhatsApp simulada (Log)". Si no se
enviará por WhatsApp real, hay que declararlo en el alcance y ajustar la justificación.

**A5. El título, la formulación y el objetivo general no nombran los mismos módulos.**
El título dice citas, servicios y cobros. La formulación dice citas, catálogo y comisiones. El
objetivo general dice citas, catálogo, calificaciones y comisiones; no nombra cobros ni
recordatorios, que sí están en los objetivos específicos. Unificar la lista en los cuatro lugares
y en el campo de acción.

**A6. Retraso respecto a su propio cronograma.**
La tabla 2 dice que el diagnóstico se hizo en septiembre, el diseño en octubre y que en noviembre
se desarrollan los sprints 1 y 2. El repositorio tiene un commit del 17/11 que dice "backend por
subir". Subir el código en el estado en que esté y rehacer el cronograma con fechas reales.

## B. Documento

**B1.** Carátula: figura "Tutor: PhD. (por definir)". Corregir a Ing. David Serrudo. La carátula
está repetida.

**B2.** Los índices listan dos figuras y dos tablas; el documento tiene cuatro tablas. Falta el
índice de contenido. Regenerar los tres índices.

**B3.** No existe el diagnóstico. El objetivo específico 1 y la matriz anuncian entrevistas,
observación con cronómetro, revisión de cuadernos y tres indicadores de línea base. No hay
resultados ni instrumentos. Es lo primero que revisará el tribunal después de los objetivos.

**B4.** Capítulos III (propuesta) y IV (conclusiones) sin iniciar.

**B5.** El documento no declara las tecnologías: no dice Laravel ni PostgreSQL, que sí aparecen en
los diagramas. Agregar la elección y su justificación, y su teoría en el capítulo II.

**B6.** Regla de comisiones sin definir. El texto dice 85 % para el barbero y 15 % para la
barbería. El modelo tiene `comision_base` en el empleado y `porcentaje_comision` en el servicio.
Escribir la regla: cuál manda, y qué pasa si difieren.

**B7.** Antecedentes nacional y local débiles: un CRM de radiotaxi de 2010 y una propuesta para
una "empresa de comercio" de 2019 sin enlace. Buscar trabajos bolivianos recientes sobre reservas
o gestión de servicios, o justificar que no existen.

**B8.** El texto habla de "siete colaboradores" en total y luego de propietario, administración y
"7 barberos". Aclarar cuántas personas son.

**B9.** La sección "Antecedentes de la investigación" del capítulo II repite la del capítulo I.
Consultar en la guía institucional en cuál de los dos capítulos debe ir.

## C. Normas APA 7

**C1.** La lista de referencias no está en orden alfabético (son tres bloques pegados).

**C2.** Schwaber y Sutherland (2020) aparece dos veces con enlaces distintos. Dejar una sola.

**C3.** "Sommerville (2019), Software engineering, 10th ed.": verificar el año de esa edición.
Además se citan dos ediciones del mismo libro (2011 y 2019); conviene usar una.

**C4.** La referencia de la Organización Internacional del Trabajo (2022) no se cita en el texto.
Citarla o quitarla.

**C5.** Se mezcla "y" con "&" entre autores. Usar "y" en todas.

**C6.** Se cita la norma ISO/IEC 25010 en su edición 2011 y se lista también la de 2023. Explicar
en una línea por qué se usa la anterior (ya está insinuado en la síntesis) y citar la 2023 donde
corresponda.

**C7.** Verificar que cada trabajo de grado citado abra desde su enlace y que las cifras citadas
(35 %, 85 %, 40 clientes) estén en la fuente. Basto Aguirre (2022) y Ojeda Palma (2019) no tienen
enlace. Guardar el PDF de cada fuente.

## D. Diagramas

**D1.** El "diagrama de clases" es el modelo de datos redibujado: atributos de tabla, sin métodos,
sin tipos de relación (asociación, agregación, composición, herencia). El tribunal pregunta la
diferencia entre ambos (cuestionario de Base de Datos, preguntas 2, 4B y 29).

**D2.** La base de datos se llama `glamstock` en la arquitectura; el proyecto se llama Blackhound.
Unificar.

**D3.** Faltan casos de uso, actividades, secuencia, componentes y despliegue, que el cronograma
da por hechos en octubre.

**D4.** En el modelo de datos, `comisiones` y `calificaciones` guardan `empleado_id` y
`servicio_id`, que ya están en la cita. Justificar la redundancia o quitarla (normalización).

**D5.** La restricción que impide citas solapadas es un buen punto técnico. Documentarla en el
diseño y preparar su explicación para la defensa.

**D6.** Subir los archivos fuente de los diagramas, no solo las imágenes.

## E. Repositorio

**E1.** Renombrar el documento a `docs/02-documento/proyecto-grado-acosta.docx`.
**E2.** Mover los tres diagramas a `docs/05-diseno/`.
**E3.** Subir el código del backend, con `README.md` de instalación y `.env.example`.
**E4.** Commits frecuentes: hoy hay uno solo.

## Para la próxima sesión

1. Decisiones A1 a A5 por escrito (media página).
2. Código subido (A6, E3).
3. Guías de entrevista y fichas de observación en `docs/03-diagnostico/`.
4. Carátula, índices y referencias corregidos (B1, B2, C1 a C5).
