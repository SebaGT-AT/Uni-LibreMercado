# Uso de herramientas exigidas por la evaluacion

## 1. XAMPP
El proyecto puede ejecutarse con XAMPP porque usa:
- Apache para servir PHP.
- PHP puro con PDO.
- MySQL o MariaDB para las tres bases de datos del sistema.

### Pasos con XAMPP
1. Copiar la carpeta del proyecto dentro de `htdocs` de XAMPP.
2. Iniciar `Apache` y `MySQL` desde el panel de XAMPP.
3. Abrir `phpMyAdmin`.
4. Ejecutar el script `sql/libre_mercado_distribuido.sql`.
5. Abrir en el navegador:
   - `http://localhost/<nombre-del-proyecto>/`

## 2. Docker
El proyecto incluye:
- `Dockerfile`
- `docker-compose.yml`

### Pasos con Docker
1. Ejecutar `docker compose up -d --build`
2. Esperar a que MySQL inicialice las bases.
3. Abrir:
   - `http://localhost:8080/`

### Justificacion academica
Docker permite demostrar portabilidad, reproduccion del entorno y despliegue controlado del sistema distribuido.

## 3. Git
Git debe usarse para versionar el proyecto y evidenciar el desarrollo por fases.

### Comandos sugeridos
```bash
git init
git add .
git commit -m "Fase 1 a 11: sistema distribuido Libre Mercado completo"
```

### Que mostrar en la defensa
- `git status`
- `git log --oneline`
- Historial de cambios por fases

## 4. Cliente de base de datos
Se recomienda usar uno de estos clientes:
- DBeaver
- HeidiSQL
- MySQL Workbench
- phpMyAdmin

### Que revisar en el cliente DB
- Existencia de las 4 bases actuales:
  - `libre_mercado_central`
  - `libre_mercado_norte`
  - `libre_mercado_centro`
  - `libre_mercado_sur`
- Tablas por nodo
- Datos de prueba
- Tabla `distributed_transactions`
- Cambios en stock despues de compras y ventas

## 5. Como defender que si cumpliste
- XAMPP: entorno local clasico para PHP/MySQL.
- Docker: entorno portable y reproducible.
- Git: control de versiones y evidencia de fases.
- Cliente DB: inspeccion y administracion visual de nodos, tablas y transacciones.
