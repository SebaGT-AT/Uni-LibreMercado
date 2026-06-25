import fs from "node:fs/promises";
import path from "node:path";
import { pathToFileURL } from "node:url";

const artifactPath =
  String.raw`C:\Users\sebag\.cache\codex-runtimes\codex-primary-runtime\dependencies\node\node_modules\@oai\artifact-tool\dist\artifact_tool.mjs`;
const { Presentation, PresentationFile } = await import(pathToFileURL(artifactPath).href);

const OUTPUT_PPTX = String.raw`C:\Users\sebag\Documents\Libre Mercado\outputs\LibreMercado-Presentacion-Mejorada.pptx`;
const TMP_ROOT = String.raw`C:\Users\sebag\AppData\Local\Temp\codex-presentations\libre-mercado-mejorada`;
const PREVIEW_DIR = path.join(TMP_ROOT, "preview");

await fs.mkdir(PREVIEW_DIR, { recursive: true });

async function saveBlob(filePath, blob) {
  await fs.mkdir(path.dirname(filePath), { recursive: true });
  await fs.writeFile(filePath, new Uint8Array(await blob.arrayBuffer()));
}

const palette = {
  paper: "#F7F1E6",
  card: "#FFFDFC",
  ink: "#1F2937",
  muted: "#52606D",
  line: "#D5DCE3",
  navy: "#1F4E79",
  blue: "#2F6FA5",
  cyan: "#D8EDF7",
  sand: "#F0E0C2",
  gold: "#D99A2B",
  mint: "#DCEEE6",
  rose: "#F5E3E0",
  slate: "#ECF1F5",
  green: "#2F855A",
  red: "#C05621",
};

const deck = Presentation.create({ slideSize: { width: 1280, height: 720 } });

function addBase(slide, title, kicker = "LIBRE MERCADO") {
  slide.background.fill = palette.paper;
  slide.shapes.add({
    geometry: "rect",
    position: { left: 0, top: 0, width: 1280, height: 86 },
    fill: palette.sand,
    line: { style: "solid", fill: palette.sand, width: 0 },
  });
  slide.shapes.add({
    geometry: "rect",
    position: { left: 72, top: 140, width: 1136, height: 1.5 },
    fill: palette.line,
    line: { style: "solid", fill: palette.line, width: 0 },
  });
  text(slide, 72, 26, 240, 20, kicker, { fontSize: 12, bold: true, color: palette.navy });
  text(slide, 72, 48, 1040, 28, title, { fontSize: 26, bold: true, color: palette.ink });
}

function card(slide, x, y, w, h, fill = palette.card, border = palette.line) {
  slide.shapes.add({
    geometry: "roundRect",
    position: { left: x, top: y, width: w, height: h },
    fill,
    line: { style: "solid", fill: border, width: 1.2 },
    borderRadius: "rounded-2xl",
    shadow: "shadow-sm",
  });
}

function text(slide, x, y, w, h, value, style = {}) {
  const shape = slide.shapes.add({
    geometry: "textbox",
    position: { left: x, top: y, width: w, height: h },
    fill: "none",
    line: { style: "solid", fill: "none", width: 0 },
  });
  shape.text = value;
  shape.text.style = {
    fontSize: 18,
    color: palette.muted,
    fontFace: "Aptos",
    ...style,
  };
  return shape;
}

function bullets(slide, x, y, w, items, style = {}) {
  const fontSize = style.fontSize || 18;
  const height = Math.max(96, items.length * (fontSize + 10) + 18);
  return text(slide, x, y, w, height, items.map((item) => "- " + item).join("\n"), style);
}

function badge(slide, x, y, w, label, fill, color = palette.ink) {
  card(slide, x, y, w, 42, fill, fill);
  text(slide, x + 14, y + 10, w - 28, 20, label, { fontSize: 16, bold: true, color });
}

function metric(slide, x, y, w, h, value, label, fill) {
  card(slide, x, y, w, h, fill, fill);
  text(slide, x + 18, y + 16, w - 36, 34, value, { fontSize: 28, bold: true, color: palette.ink });
  text(slide, x + 18, y + 54, w - 36, 42, label, { fontSize: 15, color: palette.muted });
}

function arrow(slide, x, y, w, color) {
  slide.shapes.add({
    geometry: "rect",
    position: { left: x, top: y, width: w, height: 3 },
    fill: color,
    line: { style: "solid", fill: color, width: 0 },
  });
  slide.shapes.add({
    geometry: "chevron",
    position: { left: x + w - 18, top: y - 8, width: 22, height: 19 },
    fill: color,
    line: { style: "solid", fill: color, width: 0 },
  });
}

// 1. Portada
{
  const slide = deck.slides.add();
  slide.background.fill = palette.paper;
  slide.shapes.add({
    geometry: "rect",
    position: { left: 0, top: 0, width: 1280, height: 96 },
    fill: palette.sand,
    line: { style: "solid", fill: palette.sand, width: 0 },
  });
  slide.shapes.add({
    geometry: "roundRect",
    position: { left: 758, top: 124, width: 410, height: 468 },
    fill: palette.cyan,
    line: { style: "solid", fill: palette.cyan, width: 0 },
    borderRadius: "rounded-3xl",
  });
  text(slide, 72, 34, 520, 24, "TALLER SISTEMAS DISTRIBUIDOS", { fontSize: 22, bold: true, color: palette.ink });
  text(slide, 72, 64, 520, 18, "Defensa tecnica del proyecto final", { fontSize: 14, color: palette.navy });
  text(slide, 72, 146, 520, 72, "Libre Mercado", { fontSize: 54, bold: true, color: palette.ink });
  text(
    slide,
    72,
    238,
    560,
    108,
    "Sistema de comercio electronico distribuido en PHP con tres nodos de datos, CRUD completo, autenticacion segura, transacciones ACID y estrategia CAP de tipo CP.",
    { fontSize: 24, color: palette.muted }
  );
  badge(slide, 72, 384, 168, "PHP puro + PDO", palette.card);
  badge(slide, 256, 384, 162, "MySQL / XAMPP", palette.card);
  badge(slide, 434, 384, 198, "Docker + Git + DB Client", palette.card);
  text(slide, 72, 566, 520, 20, "Objetivo: demostrar arquitectura, consistencia de datos y funcionamiento real del flujo de compra y venta.", {
    fontSize: 16,
    color: palette.muted,
  });

  metric(slide, 800, 162, 150, 102, "3", "Nodos distribuidos", palette.card);
  metric(slide, 972, 162, 150, 102, "9", "CRUD principales", palette.card);
  metric(slide, 800, 288, 150, 102, "ACID", "Venta coordinada", palette.card);
  metric(slide, 972, 288, 150, 102, "CP", "Decision CAP", palette.card);
  card(slide, 800, 420, 322, 120, palette.card);
  text(slide, 824, 444, 274, 24, "Mensaje central", { fontSize: 20, bold: true, color: palette.ink });
  text(
    slide,
    824,
    478,
    274,
    54,
    "El sistema prioriza la integridad del stock y de las ventas por sobre la disponibilidad inconsistente.",
    { fontSize: 16, color: palette.muted }
  );
}

// 2. Alcance y rubrica
{
  const slide = deck.slides.add();
  addBase(slide, "Alcance del proyecto y evidencia de cumplimiento");
  card(slide, 72, 178, 514, 442, palette.card);
  text(slide, 98, 204, 280, 28, "Que exige la evaluacion", { fontSize: 24, bold: true, color: palette.ink });
  bullets(slide, 98, 248, 452, [
    "CRUD completo sobre modulos del negocio.",
    "Arquitectura distribuida con multiples nodos.",
    "Transacciones ACID con rollback total.",
    "Eleccion CAP justificada frente a fallos.",
    "Seguridad, documentacion y stack profesional.",
  ], { fontSize: 18, color: palette.muted });

  card(slide, 626, 178, 582, 442, palette.slate);
  text(slide, 652, 204, 320, 28, "Como lo resolvimos", { fontSize: 24, bold: true, color: palette.ink });
  metric(slide, 652, 252, 160, 98, "CRUD", "Productos, clientes, usuarios, stock y mas", palette.card);
  metric(slide, 830, 252, 160, 98, "PDO", "Tres conexiones y prepared statements", palette.card);
  metric(slide, 1008, 252, 160, 98, "Roles", "ADMIN y CLIENTE con sesiones", palette.card);
  metric(slide, 652, 372, 160, 98, "2PC", "Commit / rollback simplificado", palette.card);
  metric(slide, 830, 372, 160, 98, "CP", "Consistencia y tolerancia a particiones", palette.card);
  metric(slide, 1008, 372, 160, 98, "Docs", "README y arquitectura defendible", palette.card);
  text(slide, 652, 504, 502, 72, "Esta presentacion se concentra en las partes que mas puntuan y mas facil se pueden demostrar en vivo: nodos, flujos, rollback y justificacion tecnica.", {
    fontSize: 18,
    color: palette.muted,
  });
}

// 3. Arquitectura
{
  const slide = deck.slides.add();
  addBase(slide, "Arquitectura distribuida y separacion por nodos");
  card(slide, 72, 178, 1136, 442, palette.card);
  text(slide, 104, 204, 340, 28, "Aplicacion PHP como coordinador", { fontSize: 24, bold: true, color: palette.ink });
  text(slide, 104, 240, 560, 46, "El sistema no usa una sola base. Divide el dominio en 3 nodos y cada uno tiene su propia conexion PDO.", {
    fontSize: 19,
    color: palette.muted,
  });

  card(slide, 96, 318, 280, 216, palette.cyan, palette.blue);
  text(slide, 120, 340, 180, 28, "Nodo 1", { fontSize: 26, bold: true, color: palette.ink });
  text(slide, 120, 374, 216, 24, "Productos + Stock", { fontSize: 18, bold: true, color: palette.navy });
  bullets(slide, 120, 414, 220, ["Productos", "Sucursales", "Stock por sede", "Validacion de inventario"], { fontSize: 17 });

  card(slide, 498, 318, 280, 216, palette.mint, palette.green);
  text(slide, 522, 340, 180, 28, "Nodo 2", { fontSize: 26, bold: true, color: palette.ink });
  text(slide, 522, 374, 216, 24, "Clientes + Usuarios", { fontSize: 18, bold: true, color: palette.green });
  bullets(slide, 522, 414, 220, ["Usuarios", "Clientes", "Login", "Roles y sesiones"], { fontSize: 17 });

  card(slide, 900, 318, 280, 216, palette.rose, palette.red);
  text(slide, 924, 340, 180, 28, "Nodo 3", { fontSize: 26, bold: true, color: palette.ink });
  text(slide, 924, 374, 216, 24, "Ventas + Carrito + Compras", { fontSize: 18, bold: true, color: palette.red });
  bullets(slide, 924, 414, 220, ["Carritos", "Compras", "Ventas", "distributed_transactions"], { fontSize: 17 });

  arrow(slide, 378, 426, 110, palette.navy);
  arrow(slide, 780, 426, 110, palette.navy);
  text(slide, 378, 388, 112, 20, "PDO", { fontSize: 14, bold: true, color: palette.navy, align: "center" });
  text(slide, 780, 388, 112, 20, "PDO", { fontSize: 14, bold: true, color: palette.navy, align: "center" });
}

// 4. Datos y CRUD
{
  const slide = deck.slides.add();
  addBase(slide, "Modelo de datos, CRUD y reglas del negocio");
  card(slide, 72, 178, 536, 442, palette.card);
  text(slide, 98, 204, 260, 28, "CRUD implementados", { fontSize: 24, bold: true, color: palette.ink });
  bullets(slide, 98, 248, 462, [
    "Productos",
    "Usuarios",
    "Clientes",
    "Sucursales",
    "Stock",
    "Proveedores",
    "Compras",
    "Carritos",
    "Ventas",
  ], { fontSize: 18 });

  card(slide, 648, 178, 560, 442, palette.slate);
  text(slide, 674, 204, 340, 28, "Reglas clave que conviene mencionar", { fontSize: 24, bold: true, color: palette.ink });
  bullets(slide, 674, 248, 486, [
    "Productos y usuarios usan borrado logico, no eliminacion fisica directa.",
    "Stock depende de producto + sucursal, no de una sola existencia global.",
    "Una compra aumenta inventario en Nodo 1.",
    "Una venta descuenta inventario y deja detalle de trazabilidad.",
    "Las validaciones evitan datos vacios, duplicados y acciones inconsistentes.",
  ], { fontSize: 18 });
  text(slide, 674, 520, 486, 46, "Esta slide te ayuda a demostrar que no solo hay tablas: hay reglas reales de negocio y dependencias controladas.", {
    fontSize: 17,
    color: palette.muted,
  });
}

// 5. Flujo cliente
{
  const slide = deck.slides.add();
  addBase(slide, "Flujo del cliente: carrito, compra y visualizacion");
  card(slide, 72, 178, 1136, 442, palette.card);
  badge(slide, 102, 212, 132, "1. Login", palette.cyan);
  badge(slide, 260, 212, 170, "2. Carrito", palette.cyan);
  badge(slide, 456, 212, 212, "3. Checkout / venta", palette.cyan);
  badge(slide, 694, 212, 174, "4. Mis compras", palette.cyan);
  arrow(slide, 236, 233, 20, palette.blue);
  arrow(slide, 432, 233, 20, palette.blue);
  arrow(slide, 670, 233, 20, palette.blue);

  card(slide, 98, 294, 258, 248, palette.card);
  bullets(slide, 118, 320, 220, [
    "Usuario CLIENTE inicia sesion.",
    "Nodo 2 valida credenciales con password_verify().",
    "Se crea la sesion y se carga el dashboard segun rol.",
  ], { fontSize: 17 });

  card(slide, 388, 294, 258, 248, palette.card);
  bullets(slide, 408, 320, 220, [
    "El cliente crea y edita su propio carrito.",
    "Solo ve sus registros, no los de otros usuarios.",
    "Se agregan productos y cantidades.",
  ], { fontSize: 17 });

  card(slide, 678, 294, 258, 248, palette.card);
  bullets(slide, 698, 320, 220, [
    "Elige sucursal para descontar stock.",
    "La venta valida inventario antes de confirmar.",
    "Si algo falla, se aborta completa.",
  ], { fontSize: 17 });

  card(slide, 968, 294, 210, 248, palette.card);
  bullets(slide, 988, 320, 172, [
    "El cliente revisa su historial.",
    "Ve fecha, sucursal, total y detalle.",
    "Aporta trazabilidad funcional en la demo.",
  ], { fontSize: 16 });
}

// 6. ACID + rollback
{
  const slide = deck.slides.add();
  addBase(slide, "Venta distribuida con ACID y rollback total");
  card(slide, 72, 178, 714, 442, palette.card);
  text(slide, 98, 204, 280, 28, "Secuencia de la venta", { fontSize: 24, bold: true, color: palette.ink });
  bullets(slide, 98, 248, 642, [
    "1. Se recibe cliente, sucursal y productos.",
    "2. Se registra PREPARED en distributed_transactions.",
    "3. Se abren transacciones en Nodo 1 y Nodo 3.",
    "4. Se verifica stock disponible por sucursal.",
    "5. Se descuenta stock y se crea venta + detalle.",
    "6. Si todo esta correcto, ambos nodos hacen COMMIT.",
    "7. Si cualquier paso falla, ambos nodos hacen ROLLBACK.",
  ], { fontSize: 18 });

  card(slide, 824, 178, 384, 208, palette.mint);
  text(slide, 850, 204, 210, 28, "ACID en lenguaje simple", { fontSize: 22, bold: true, color: palette.ink });
  bullets(slide, 850, 280, 306, [
    "Atomicidad: todo o nada.",
    "Consistencia: nunca queda stock negativo.",
    "Aislamiento: una venta no pisa a otra.",
    "Durabilidad: lo confirmado persiste.",
  ], { fontSize: 17 });

  card(slide, 824, 412, 384, 208, palette.rose);
  text(slide, 850, 438, 214, 28, "Como demostrar el rollback", { fontSize: 22, bold: true, color: palette.ink });
  bullets(slide, 850, 520, 306, [
    "Provocas un error en el nodo de stock.",
    "La venta no se registra como confirmada.",
    "El stock queda intacto y la transaccion termina ABORTED.",
  ], { fontSize: 17 });
}

// 7. CAP
{
  const slide = deck.slides.add();
  addBase(slide, "CAP: por que elegimos CP ante una particion de red");
  card(slide, 72, 178, 320, 442, palette.cyan);
  text(slide, 112, 236, 120, 80, "CP", { fontSize: 74, bold: true, color: palette.navy });
  text(slide, 112, 322, 220, 54, "Consistency + Partition Tolerance", { fontSize: 24, bold: true, color: palette.ink });
  text(slide, 112, 402, 218, 116, "En un e-commerce academico, inventario y ventas correctas importan mas que seguir vendiendo con datos inconsistentes.", {
    fontSize: 19,
    color: palette.muted,
  });

  card(slide, 430, 178, 778, 198, palette.card);
  text(slide, 458, 204, 250, 28, "Que priorizamos", { fontSize: 24, bold: true, color: palette.ink });
  bullets(slide, 458, 248, 696, [
    "Consistencia: el stock y la venta deben coincidir entre nodos.",
    "Tolerancia a particiones: si la red se corta, el sistema detecta el problema.",
    "No permitimos una venta parcial que deje datos divergentes.",
  ], { fontSize: 18 });

  card(slide, 430, 402, 778, 218, palette.slate);
  text(slide, 458, 428, 340, 28, "Que sacrificamos y como lo explicas", { fontSize: 24, bold: true, color: palette.ink });
  bullets(slide, 458, 472, 696, [
    "Sacrificamos disponibilidad: si el nodo de stock no responde, la venta se cancela.",
    "Eso es intencional, porque vender sin stock confirmado seria inconsistente.",
    "La recuperacion ocurre reintentando cuando el nodo vuelve a estar disponible.",
  ], { fontSize: 18 });
}

// 8. Herramientas y demo
{
  const slide = deck.slides.add();
  addBase(slide, "Herramientas del entorno y guion de demostracion");
  metric(slide, 72, 188, 246, 104, "Docker", "Levanta web + MySQL con compose", palette.card);
  metric(slide, 336, 188, 246, 104, "XAMPP", "Alternativa local para PHP / MySQL", palette.card);
  metric(slide, 600, 188, 246, 104, "Git", "Versionado y respaldo del avance", palette.card);
  metric(slide, 864, 188, 344, 104, "Cliente DB", "DBeaver, Workbench o phpMyAdmin", palette.card);

  card(slide, 72, 336, 520, 284, palette.card);
  text(slide, 98, 362, 242, 28, "Comandos utiles", { fontSize: 24, bold: true, color: palette.ink });
  bullets(slide, 98, 406, 442, [
    "docker compose up -d --build",
    "docker compose ps",
    "docker compose logs web",
    "docker compose logs db",
    "docker compose down",
  ], { fontSize: 18 });

  card(slide, 626, 336, 582, 284, palette.slate);
  text(slide, 652, 362, 260, 28, "Demo recomendada en la defensa", { fontSize: 24, bold: true, color: palette.ink });
  bullets(slide, 652, 444, 506, [
    "1. Mostrar login y roles.",
    "2. Crear una compra y verificar aumento de stock.",
    "3. Crear una venta desde el flujo cliente.",
    "4. Revisar detalle de venta y tabla distributed_transactions.",
    "5. Explicar que pasaria si el nodo de stock falla.",
  ], { fontSize: 18 });
}

// 9. Cierre
{
  const slide = deck.slides.add();
  slide.background.fill = palette.paper;
  slide.shapes.add({
    geometry: "roundRect",
    position: { left: 106, top: 92, width: 1068, height: 536 },
    fill: palette.card,
    line: { style: "solid", fill: palette.line, width: 1.2 },
    borderRadius: "rounded-3xl",
    shadow: "shadow-sm",
  });
  text(slide, 176, 150, 420, 26, "CIERRE", { fontSize: 20, bold: true, color: palette.navy });
  text(slide, 176, 190, 760, 60, "Libre Mercado cumple la evaluacion con una defensa tecnica clara y demostrable", {
    fontSize: 34,
    bold: true,
    color: palette.ink,
  });
  bullets(slide, 176, 292, 784, [
    "Arquitectura distribuida real con tres nodos y tres conexiones PDO.",
    "CRUD funcional con reglas de negocio y borrado logico donde corresponde.",
    "Venta con transaccion coordinada, commit, rollback total y evidencia ACID.",
    "Eleccion CP justificada frente a una falla de conexion entre nodos.",
    "Stack profesional para exponer: Docker, XAMPP, Git y cliente de base de datos.",
  ], { fontSize: 21, color: palette.muted });
  metric(slide, 930, 182, 160, 94, "3", "Nodos", palette.cyan);
  metric(slide, 930, 300, 160, 94, "ACID", "Rollback total", palette.mint);
  metric(slide, 930, 418, 160, 94, "CP", "Consistencia primero", palette.rose);
  text(slide, 176, 572, 760, 22, "Frase final sugerida: preferimos bloquear una venta antes que comprometer la integridad del sistema.", {
    fontSize: 17,
    color: palette.navy,
    italic: true,
  });
}

for (const [index, slide] of deck.slides.items.entries()) {
  const png = await deck.export({ slide, format: "png", scale: 1 });
  await saveBlob(path.join(PREVIEW_DIR, `slide-${String(index + 1).padStart(2, "0")}.png`), png);
}

const pptx = await PresentationFile.exportPptx(deck);
await pptx.save(OUTPUT_PPTX);
console.log(`Generated ${OUTPUT_PPTX}`);
