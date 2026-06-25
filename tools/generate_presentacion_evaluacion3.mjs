import fs from "node:fs/promises";
import os from "node:os";
import path from "node:path";
import { fileURLToPath } from "node:url";
import { Presentation, PresentationFile } from "@oai/artifact-tool";

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const projectRoot = path.resolve(__dirname, "..");
const outputPptx = path.join(projectRoot, "outputs", "LibreMercado-Presentacion-Evaluacion3.pptx");
const workspace = path.join(os.tmpdir(), "codex-presentations", "libremercado-evaluacion3");
const tmpDir = path.join(workspace, "tmp");
const previewDir = path.join(tmpDir, "preview");
const layoutDir = path.join(tmpDir, "layout");
const qaDir = path.join(tmpDir, "qa");

const slideSize = { width: 1280, height: 720 };
const page = { left: 72, top: 110, width: 1136, height: 560 };
const colors = {
  navy: "0F2742",
  blue: "1B5E8A",
  teal: "2D8C8C",
  sand: "F2EBDD",
  cream: "FBF8F1",
  white: "FFFFFF",
  slate: "4F5D73",
  ink: "1E293B",
  green: "2E7D32",
  red: "C62828",
  gold: "D4A017",
  line: "D8DDE6",
};

async function ensureDirs() {
  await fs.mkdir(path.dirname(outputPptx), { recursive: true });
  await fs.mkdir(previewDir, { recursive: true });
  await fs.mkdir(layoutDir, { recursive: true });
  await fs.mkdir(qaDir, { recursive: true });
}

async function writeBlob(filePath, blob) {
  await fs.mkdir(path.dirname(filePath), { recursive: true });
  await fs.writeFile(filePath, new Uint8Array(await blob.arrayBuffer()));
}

function addText(slide, text, position, style = {}) {
  const shape = slide.shapes.add({
    geometry: "textbox",
    position,
    fill: "none",
    line: { style: "solid", fill: "none", width: 0 },
  });
  shape.text = text;
  shape.text.style = {
    fontFace: "Aptos",
    color: colors.ink,
    fontSize: 20,
    breakLine: false,
    ...style,
  };
  return shape;
}

function addHeader(slide, title, subtitle = "LIBRE MERCADO") {
  slide.background.fill = colors.cream;
  slide.shapes.add({
    geometry: "rect",
    position: { left: 0, top: 0, width: slideSize.width, height: 88 },
    fill: colors.navy,
    line: { style: "solid", fill: colors.navy, width: 0 },
  });

  addText(slide, subtitle, { left: 72, top: 24, width: 240, height: 20 }, {
    fontFace: "Aptos Display",
    fontSize: 14,
    bold: true,
    color: colors.sand,
  });
  addText(slide, title, { left: 72, top: 46, width: 1040, height: 30 }, {
    fontFace: "Aptos Display",
    fontSize: 30,
    bold: true,
    color: colors.white,
  });
  slide.shapes.add({
    geometry: "line",
    position: { left: 72, top: 104, width: 1136, height: 1 },
    line: { style: "solid", fill: colors.line, width: 1.5 },
  });
}

function addCard(slide, x, y, w, h, opts = {}) {
  return slide.shapes.add({
    geometry: "roundRect",
    position: { left: x, top: y, width: w, height: h },
    fill: opts.fill ?? colors.white,
    line: { style: "solid", fill: opts.line ?? colors.line, width: 1 },
    borderRadius: "rounded-xl",
    shadow: "shadow-sm",
  });
}

function addBulletBlock(slide, title, bullets, x, y, w, h, opts = {}) {
  addCard(slide, x, y, w, h, opts);
  addText(slide, title, { left: x + 22, top: y + 20, width: w - 44, height: 24 }, {
    fontFace: "Aptos Display",
    fontSize: 22,
    bold: true,
    color: opts.titleColor ?? colors.navy,
  });
  addText(slide, bullets.map((b) => `- ${b}`).join("\n"), { left: x + 22, top: y + 60, width: w - 44, height: h - 84 }, {
    fontSize: 18,
    color: colors.ink,
    breakLine: true,
  });
}

function addMetric(slide, x, y, w, h, value, label, fill) {
  addCard(slide, x, y, w, h, { fill, line: fill });
  addText(slide, value, { left: x + 16, top: y + 14, width: w - 32, height: 42 }, {
    fontFace: "Aptos Display",
    fontSize: 34,
    bold: true,
    color: colors.white,
    align: "center",
  });
  addText(slide, label, { left: x + 16, top: y + 58, width: w - 32, height: 36 }, {
    fontSize: 16,
    color: colors.white,
    align: "center",
    breakLine: true,
  });
}

function slide1(presentation) {
  const slide = presentation.slides.add();
  slide.background.fill = colors.cream;
  slide.shapes.add({
    geometry: "rect",
    position: { left: 0, top: 0, width: slideSize.width, height: slideSize.height },
    fill: colors.cream,
    line: { style: "solid", fill: colors.cream, width: 0 },
  });
  slide.shapes.add({
    geometry: "roundRect",
    position: { left: 748, top: 118, width: 420, height: 470 },
    fill: colors.white,
    line: { style: "solid", fill: colors.line, width: 1 },
    borderRadius: "rounded-2xl",
    shadow: "shadow-sm",
  });

  addText(slide, "TALLER SISTEMAS DISTRIBUIDOS", { left: 72, top: 34, width: 520, height: 24 }, {
    fontFace: "Aptos Display",
    fontSize: 24,
    bold: true,
    color: colors.navy,
  });
  addText(slide, "Tercera evaluacion: transacciones distribuidas, fallos y despliegue LAN", { left: 72, top: 64, width: 620, height: 24 }, {
    fontSize: 17,
    color: colors.blue,
  });
  addText(slide, "Libre Mercado", { left: 72, top: 148, width: 520, height: 72 }, {
    fontFace: "Aptos Display",
    fontSize: 52,
    bold: true,
    color: colors.navy,
  });
  addText(slide, "Sistema distribuido en PHP que coordina compras, ventas y stock entre un nodo central y tres sucursales, con procedimientos almacenados, AJAX y politica CP.", { left: 72, top: 238, width: 590, height: 120 }, {
    fontSize: 22,
    color: colors.slate,
    breakLine: true,
  });

  addMetric(slide, 798, 166, 150, 102, "4", "Nodos DB", colors.blue);
  addMetric(slide, 970, 166, 150, 102, "9", "CRUD listos", colors.teal);
  addMetric(slide, 798, 294, 150, 102, "ACID", "Venta coordinada", colors.gold);
  addMetric(slide, 970, 294, 150, 102, "CP", "CAP elegido", colors.navy);

  addBulletBlock(slide, "Mensaje central", [
    "Si una sucursal falla, solo esa sucursal deja de vender.",
    "Las demas siguen operando.",
    "Se protege la integridad del stock antes que vender inconsistente.",
  ], 798, 420, 322, 122, { fill: "F8FBFF", titleColor: colors.navy });

  addText(slide, "Entorno: Docker para nodos, XAMPP como alternativa local, Git y cliente DB para inspeccion.", { left: 72, top: 572, width: 580, height: 24 }, {
    fontSize: 18,
    color: colors.slate,
  });
  return slide;
}

function slide2(presentation) {
  const slide = presentation.slides.add();
  addHeader(slide, "Que exige la tercera evaluacion y como se cubre");
  addBulletBlock(slide, "Exigencias del documento", [
    "Compra distribuida entre sucursales.",
    "Transacciones distribuidas con rollback total.",
    "Procedimientos almacenados en MySQL.",
    "Simulacion de caida y recuperacion de nodos.",
    "PHP + AJAX como capa cliente-servidor.",
    "Justificacion tecnica del modelo CAP.",
  ], 72, 176, 520, 442);
  addBulletBlock(slide, "Cobertura en el proyecto", [
    "Panel de nodos para marcar ONLINE / OFFLINE.",
    "Checkout AJAX desde el flujo del cliente.",
    "sp_realizar_compra, sp_actualizar_stock y sp_reconstruir_stock.",
    "distributed_transactions para PREPARED / COMMITTED / ABORTED.",
    "Docker Compose para simular LAN y multiples servicios.",
    "README y documento de arquitectura para la defensa.",
  ], 616, 176, 592, 442, { fill: "F8FBFF" });
  return slide;
}

function slide3(presentation) {
  const slide = presentation.slides.add();
  addHeader(slide, "Arquitectura LAN: coordinador, nodo central y sucursales");
  addText(slide, "La aplicacion PHP expone la web en LAN y coordina cuatro bases de datos separadas. Cada sucursal conserva su operacion local.", { left: 72, top: 150, width: 1136, height: 38 }, {
    fontSize: 20,
    color: colors.slate,
  });

  addCard(slide, 96, 250, 230, 210, { fill: "F8FBFF" });
  addText(slide, "Nodo Central", { left: 120, top: 276, width: 180, height: 28 }, { fontFace: "Aptos Display", fontSize: 24, bold: true, color: colors.navy });
  addText(slide, "Base: libre_mercado_central", { left: 120, top: 310, width: 180, height: 24 }, { fontSize: 16, color: colors.blue });
  addText(slide, "- productos\n- sucursales\n- usuarios y clientes\n- proveedores\n- ventas y detalle\n- node_status\n- tx distribuidas", { left: 120, top: 350, width: 180, height: 140 }, { fontSize: 15, breakLine: true });

  addCard(slide, 374, 250, 230, 210, { fill: colors.white });
  addText(slide, "Sucursal Norte", { left: 398, top: 276, width: 180, height: 28 }, { fontFace: "Aptos Display", fontSize: 24, bold: true, color: colors.navy });
  addText(slide, "Base: libre_mercado_norte", { left: 398, top: 310, width: 180, height: 24 }, { fontSize: 16, color: colors.blue });
  addText(slide, "- stock\n- carrito\n- detalle_carrito\n- compras\n- detalle_compras\n- stock_movimientos", { left: 398, top: 350, width: 180, height: 132 }, { fontSize: 15, breakLine: true });

  addCard(slide, 652, 250, 230, 210, { fill: colors.white });
  addText(slide, "Sucursal Centro", { left: 676, top: 276, width: 180, height: 28 }, { fontFace: "Aptos Display", fontSize: 24, bold: true, color: colors.navy });
  addText(slide, "Base: libre_mercado_centro", { left: 676, top: 310, width: 180, height: 24 }, { fontSize: 16, color: colors.blue });
  addText(slide, "- stock local\n- carrito local\n- compras locales\n- historial de movimientos", { left: 676, top: 350, width: 180, height: 132 }, { fontSize: 15, breakLine: true });

  addCard(slide, 930, 250, 230, 210, { fill: colors.white });
  addText(slide, "Sucursal Sur", { left: 954, top: 276, width: 180, height: 28 }, { fontFace: "Aptos Display", fontSize: 24, bold: true, color: colors.navy });
  addText(slide, "Base: libre_mercado_sur", { left: 954, top: 310, width: 180, height: 24 }, { fontSize: 16, color: colors.blue });
  addText(slide, "- stock local\n- carrito local\n- compras locales\n- historial de movimientos", { left: 954, top: 350, width: 180, height: 132 }, { fontSize: 15, breakLine: true });

  addText(slide, "Despliegue LAN: otros equipos entran por navegador a http://IP_DEL_HOST:8080 mientras Docker mantiene separados web y nodos.", { left: 96, top: 530, width: 1064, height: 42 }, { fontSize: 18, color: colors.slate, breakLine: true });
  return slide;
}

function slide4(presentation) {
  const slide = presentation.slides.add();
  addHeader(slide, "CRUD, reglas de negocio y seguridad");
  addBulletBlock(slide, "CRUD cubiertos", [
    "Productos",
    "Usuarios",
    "Clientes",
    "Sucursales",
    "Stock",
    "Proveedores",
    "Compras",
    "Carritos",
    "Ventas",
  ], 72, 176, 400, 442);
  addBulletBlock(slide, "Reglas clave", [
    "Productos y usuarios usan borrado logico.",
    "Stock depende de producto + sucursal.",
    "Compras incrementan inventario local.",
    "Ventas descuentan inventario local.",
    "Las tablas detalle dejan trazabilidad.",
    "Preparacion / commit / abort se registran centralmente.",
  ], 496, 176, 352, 442, { fill: "F8FBFF" });
  addBulletBlock(slide, "Seguridad", [
    "password_hash() y password_verify().",
    "Sesiones por rol ADMIN / CLIENTE.",
    "PDO con prepared statements.",
    "Validaciones de formularios.",
    "Separacion MVC simple.",
  ], 872, 176, 336, 442);
  return slide;
}

function slide5(presentation) {
  const slide = presentation.slides.add();
  addHeader(slide, "Flujo del cliente con PHP + AJAX");
  addCard(slide, 72, 180, 1136, 412, { fill: colors.white });
  const xs = [102, 312, 560, 836];
  const ws = [170, 210, 230, 240];
  ["1. Login", "2. Carrito", "3. Checkout AJAX", "4. Mis compras"].forEach((label, idx) => {
    addCard(slide, xs[idx], 212, ws[idx], 42, { fill: "EAF4FA", line: "EAF4FA" });
    addText(slide, label, { left: xs[idx] + 14, top: 222, width: ws[idx] - 28, height: 20 }, { fontSize: 17, bold: true, color: colors.navy, align: "center" });
  });
  addText(slide, "- Nodo central valida usuario y rol.\n- Se abre sesion segura.\n- El dashboard cambia segun ADMIN o CLIENTE.", { left: 118, top: 320, width: 138, height: 130 }, { fontSize: 17, breakLine: true });
  addText(slide, "- El cliente trabaja solo con sus carritos.\n- Selecciona sucursal de trabajo.\n- Define productos y cantidades.", { left: 338, top: 320, width: 158, height: 130 }, { fontSize: 17, breakLine: true });
  addText(slide, "- El formulario llama api/checkout.php.\n- La respuesta vuelve en JSON.\n- Si la sucursal esta OFFLINE, el checkout se bloquea.\n- Si esta ONLINE, confirma y redirige.", { left: 586, top: 320, width: 182, height: 156 }, { fontSize: 17, breakLine: true });
  addText(slide, "- El cliente revisa ventas propias.\n- Puede mostrar fecha, sucursal, total y detalle.\n- Eso sirve como evidencia de trazabilidad funcional.", { left: 862, top: 320, width: 190, height: 156 }, { fontSize: 17, breakLine: true });
  addText(slide, "AJAX no reemplaza la logica distribuida: solo mejora la experiencia y permite demostrar un flujo cliente-servidor moderno en la evaluacion.", { left: 102, top: 520, width: 1000, height: 32 }, { fontSize: 18, color: colors.slate });
  return slide;
}

function slide6(presentation) {
  const slide = presentation.slides.add();
  addHeader(slide, "Stored procedures, ACID y Two-Phase Commit simplificado");
  addBulletBlock(slide, "Procedimientos almacenados", [
    "sp_registrar_venta",
    "sp_registrar_detalle_venta",
    "sp_actualizar_total_venta",
    "sp_realizar_compra",
    "sp_actualizar_stock",
    "sp_reconstruir_stock",
    "sp_set_node_status",
  ], 72, 176, 330, 442);
  addBulletBlock(slide, "Secuencia distribuida", [
    "1. El backend registra PREPARED.",
    "2. Se abren transacciones en nodo central y nodo sucursal.",
    "3. El stored procedure de sucursal valida y descuenta stock.",
    "4. El central registra venta y detalle.",
    "5. Si todo sale bien: COMMITTED.",
    "6. Si algo falla: rollback y ABORTED.",
  ], 426, 176, 390, 442, { fill: "F8FBFF" });
  addBulletBlock(slide, "ACID en simple", [
    "Atomicidad: la venta completa o no existe.",
    "Consistencia: no se vende sin stock valido.",
    "Aislamiento: una operacion no deja estados parciales visibles.",
    "Durabilidad: el commit persiste en InnoDB.",
  ], 840, 176, 368, 208);
  addBulletBlock(slide, "2PC simplificado", [
    "PREPARED en distributed_transactions.",
    "COMMIT doble cuando ambos nodos responden.",
    "ABORTED y reconstruccion si una etapa falla.",
  ], 840, 410, 368, 208, { fill: "FFF7E8", titleColor: colors.gold });
  return slide;
}

function slide7(presentation) {
  const slide = presentation.slides.add();
  addHeader(slide, "Falla de nodos, rollback y recuperacion");
  addCard(slide, 72, 178, 310, 442, { fill: "F8FBFF" });
  addText(slide, "Escenario", { left: 106, top: 206, width: 180, height: 28 }, { fontFace: "Aptos Display", fontSize: 24, bold: true, color: colors.navy });
  addText(slide, "Desde nodos.php el admin puede marcar una sucursal OFFLINE. Eso simula particion o indisponibilidad local.", { left: 106, top: 252, width: 240, height: 112 }, { fontSize: 19, breakLine: true });
  addText(slide, "Resultado esperado:\nsolo se bloquean compras hacia esa sucursal.", { left: 106, top: 390, width: 230, height: 78 }, { fontSize: 19, breakLine: true, bold: true, color: colors.red });

  addBulletBlock(slide, "Como demuestras rollback", [
    "Intentas comprar usando la sucursal marcada OFFLINE.",
    "El backend detecta el estado del nodo antes de vender.",
    "La venta queda cancelada.",
    "No aparece commit confirmado.",
    "El stock no se altera.",
    "La transaccion queda ABORTED o no llega a confirmarse.",
  ], 414, 178, 388, 442);

  addBulletBlock(slide, "Recuperacion", [
    "El admin vuelve a dejar la sucursal ONLINE.",
    "Si hubo una operacion abortada tras descontar, sp_reconstruir_stock recompone el inventario.",
    "Luego se puede reintentar la compra.",
  ], 834, 178, 374, 210);

  addBulletBlock(slide, "Mensaje para la defensa", [
    "La gracia no es que nunca falle.",
    "La gracia es fallar de forma controlada, consistente y recuperable.",
  ], 834, 412, 374, 208, { fill: "FFF7E8", titleColor: colors.gold });
  return slide;
}

function slide8(presentation) {
  const slide = presentation.slides.add();
  addHeader(slide, "CAP, CP y despliegue en LAN");
  addCard(slide, 72, 178, 260, 442, { fill: colors.navy, line: colors.navy });
  addText(slide, "CP", { left: 110, top: 234, width: 120, height: 80 }, { fontFace: "Aptos Display", fontSize: 72, bold: true, color: colors.white, align: "center" });
  addText(slide, "Consistency + Partition Tolerance", { left: 110, top: 324, width: 184, height: 56 }, { fontSize: 20, color: colors.sand, breakLine: true, align: "center" });
  addText(slide, "Preferimos bloquear una venta antes que dejar stock y ventas divergentes.", { left: 110, top: 410, width: 184, height: 114 }, { fontSize: 20, color: colors.white, breakLine: true, align: "center" });

  addBulletBlock(slide, "Que priorizamos", [
    "Consistencia entre venta central y stock local.",
    "Tolerancia a particiones: una sucursal puede quedar aislada.",
    "Aislamiento del problema: una sucursal caida no tumba a las otras.",
  ], 364, 178, 392, 200, { fill: "F8FBFF" });
  addBulletBlock(slide, "Que sacrificamos", [
    "Disponibilidad de la sucursal afectada.",
    "Si una sede esta OFFLINE, esa sede deja de vender temporalmente.",
    "Es una decision intencional y justificable academicamente.",
  ], 364, 402, 392, 218);
  addBulletBlock(slide, "Como se publica en LAN", [
    "Docker Compose levanta web y bases por separado.",
    "Solo se expone la web en el puerto 8080.",
    "Otros equipos entran por http://IP_DEL_HOST:8080.",
    "XAMPP queda como alternativa local, no como topologia principal.",
  ], 788, 178, 420, 442, { fill: colors.white });
  return slide;
}

function slide9(presentation) {
  const slide = presentation.slides.add();
  slide.background.fill = colors.cream;
  addCard(slide, 106, 92, 1068, 536, { fill: colors.white });
  addText(slide, "CIERRE", { left: 176, top: 150, width: 420, height: 26 }, { fontFace: "Aptos Display", fontSize: 24, bold: true, color: colors.navy });
  addText(slide, "Libre Mercado queda listo para una defensa tecnica clara, visible y demostrable", { left: 176, top: 190, width: 760, height: 64 }, { fontFace: "Aptos Display", fontSize: 34, bold: true, color: colors.navy, breakLine: true });
  addText(slide, "- Arquitectura distribuida con nodo central + tres sucursales.\n- CRUD completos y reglas de negocio reales.\n- PHP + AJAX para el flujo cliente-servidor.\n- Stored procedures para compra, stock y recuperacion.\n- ACID y 2PC simplificado con evidencia en distributed_transactions.\n- CAP de tipo CP con bloqueo por sucursal afectada.\n- Despliegue accesible por LAN para que otros prueben la pagina.", { left: 176, top: 300, width: 720, height: 220 }, { fontSize: 21, breakLine: true });
  addMetric(slide, 930, 182, 160, 94, "LAN", "Acceso web", colors.blue);
  addMetric(slide, 930, 300, 160, 94, "ACID", "Rollback total", colors.teal);
  addMetric(slide, 930, 418, 160, 94, "CP", "Consistencia primero", colors.navy);
  addText(slide, "Frase final sugerida: el sistema no solo funciona cuando todo esta sano; tambien responde correctamente cuando una sucursal falla.", { left: 176, top: 572, width: 760, height: 22 }, { fontSize: 16, color: colors.slate });
  return slide;
}

async function main() {
  await ensureDirs();

  const sourceNotes = [
    "Fuente principal: Tercera Evaluacion SD_2026.pdf (usuario-proporcionado).",
    "Contenido adicional: estado actual del proyecto Libre Mercado en el workspace.",
    "Hechos incluidos: arquitectura central + sucursales, Docker Compose, panel de nodos, AJAX checkout, stored procedures y CAP CP.",
    "Uso previsto: presentacion editable de defensa academica.",
  ].join("\n");
  const slidePlan = [
    "Paleta: navy #0F2742, blue #1B5E8A, teal #2D8C8C, gold #D4A017, cream #FBF8F1.",
    "Tipografia: Aptos Display para titulos, Aptos para cuerpo.",
    "Escala: titulos 30-52, cuerpo 16-22, metricas 34-72.",
    "Narrativa: portada, requisitos, arquitectura, CRUD/seguridad, flujo cliente+AJAX, stored procedures+ACID, fallos+rollback, CAP+LAN, cierre.",
  ].join("\n");

  await fs.writeFile(path.join(tmpDir, "source-notes.txt"), sourceNotes, "utf8");
  await fs.writeFile(path.join(tmpDir, "slide-plan.txt"), slidePlan, "utf8");

  const presentation = Presentation.create({ slideSize });
  slide1(presentation);
  slide2(presentation);
  slide3(presentation);
  slide4(presentation);
  slide5(presentation);
  slide6(presentation);
  slide7(presentation);
  slide8(presentation);
  slide9(presentation);

  for (const [index, slide] of presentation.slides.items.entries()) {
    const stem = `slide-${String(index + 1).padStart(2, "0")}`;
    const png = await presentation.export({ slide, format: "png", scale: 1 });
    await writeBlob(path.join(previewDir, `${stem}.png`), png);
    const layout = await slide.export({ format: "layout" });
    await fs.writeFile(path.join(layoutDir, `${stem}.layout.json`), await layout.text(), "utf8");
  }

  const montage = await presentation.export({ format: "webp", montage: true, scale: 1 });
  await writeBlob(path.join(previewDir, "deck-montage.webp"), montage);

  const qa = [
    "Revision visual inicial completada por render PNG y montage.",
    "Validar manualmente que no haya cortes de texto tras exportar.",
    "Comprobar que los comandos LAN y la arquitectura coinciden con Docker Compose actual.",
  ].join("\n");
  await fs.writeFile(path.join(qaDir, "visual-qa.txt"), qa, "utf8");

  const pptx = await PresentationFile.exportPptx(presentation);
  await pptx.save(outputPptx);

  console.log(JSON.stringify({
    outputPptx,
    workspace,
    previewDir,
    layoutDir,
    qaDir,
    slideCount: presentation.slides.items.length,
  }, null, 2));
}

main().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});
