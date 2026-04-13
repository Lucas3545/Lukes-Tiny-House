<?php
$pageTitle = 'Calendario de Reservas';
$pageStyles = ['./css/estilos-calen.css'];
include __DIR__ . '/includes/page-start.php';
?>
    <header>
        <h1 class="calendar-title">Calendario de Reservas</h1>
    </header>
    <main>
        <details>
            <summary>&#9776;</summary>
            <li><a href="index.php" class="nav-item" title="Inicio">⏪Inicio</a></li>
            <li><a href="panel-de-acceso.php" class="nav-item" title="Panel de Control">🟩Panel de Acceso</a></li>
            <li><a href="informacion.php" class="nav-item" title="Información">🗄️Información</a></li>
            <li><a href="galeria.php" class="nav-item" title="Galeria">🖼️Galeria</a></li>
            <li><a href="mailto:lucaszv2006@gmail.com" class="nav-item">📧gmail</a></li>
            <li><a href="tel:+50683256836" class="nav-item" title="Contacto">📞Contacto</a></li>


        </details>
        <div class="calendar-container">
            <div class="calendar-header">
                <button id="prevMonth">&lt;</button>
                <span id="monthYear"></span>
                <button id="nextMonth">&gt;</button>
            </div>
            <div class="calendar-grid" id="calendarDays"></div>
            <div class="calendar-grid" id="calendar"></div>
            <div class="reservation-form" id="reservationForm" style="display:none;">
                <h3>Reservar noche(s)</h3>
                <p id="selectedDate"></p>
                <input type="text" id="nombre" placeholder="Tu nombre" required>
                <input type="email" id="email" placeholder="Tu correo" required>
                <input type="number" id="noches" placeholder="Cantidad de noches" min="1" max="30" value="1" required>
                <button id="confirmReservation">Confirmar Reserva</button>
                <button id="cancelReservation" type="button" style="display:none;">Cancelar Reserva</button>
                <div class="success-message" id="successMsg" style="display:none;">Reserva realizada con exito</div>
                <p class="reservation-hint" id="reservationHint">Usa el correo con el que te registraste en el sitio. Si ya iniciaste sesión, este campo se completará automáticamente.</p>
            </div>
        </div>
        <div>
            <legend><strong><a> Nota: </a></strong></legend>
            <p>Se va a reservar con anticipacion y se te va a confirmar en las proximas 24h. Por favor estar atento a su reserva</p>
        </div>
    </main>

    <?php include './includes/footer.php'; ?>

    <script>
        let unavailableDates = [];
        let selectedCell = null;
        let selectedDate = null;
        let reservationMode = 'create';
        let currentMonth = new Date().getMonth();
        let currentYear = new Date().getFullYear();


        const API_URL = './reservas.php';

        const monthNames = [
            "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio",
            "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"
        ];

        async function cargarReservas(month, year) {
            try {
                const response = await fetch(`${API_URL}?mes=${month + 1}&anio=${year}`);
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                const data = await response.json();
                if (data.success) {
                    unavailableDates = data.reservas.map(r => r.fecha);
                } else {
                    throw new Error(data.message || 'No se pudieron cargar las reservas');
                }
            } catch (error) {
                console.error('Error al cargar reservas:', error);
            }
        }

        async function renderCalendar(month, year) {
            await cargarReservas(month, year);

            document.getElementById('monthYear').textContent = `${monthNames[month]} ${year}`;
            const daysGrid = document.getElementById('calendarDays');
            const calendarGrid = document.getElementById('calendar');
            daysGrid.innerHTML = '';
            calendarGrid.innerHTML = '';

            const days = ['Dom', 'Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab'];
            days.forEach(d => {
                const dayDiv = document.createElement('div');
                dayDiv.className = 'calendar-day';
                dayDiv.textContent = d;
                daysGrid.appendChild(dayDiv);
            });

            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();

            for (let i = 0; i < firstDay; i++) {
                const emptyCell = document.createElement('div');
                emptyCell.className = 'calendar-cell';
                emptyCell.style.visibility = 'hidden';
                calendarGrid.appendChild(emptyCell);
            }

            for (let day = 1; day <= daysInMonth; day++) {
                const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                const cell = document.createElement('div');
                cell.className = 'calendar-cell';
                cell.textContent = day;
                const isUnavailable = unavailableDates.includes(dateStr);

                if (isUnavailable) {
                    cell.classList.add('unavailable');
                }
                cell.addEventListener('click', function () {
                    if (selectedCell) selectedCell.classList.remove('selected');
                    cell.classList.add('selected');
                    selectedCell = cell;
                    selectedDate = dateStr;
                    reservationMode = isUnavailable ? 'cancel' : 'create';

                    const confirmBtn = document.getElementById('confirmReservation');
                    const cancelBtn = document.getElementById('cancelReservation');
                    const nombreInput = document.getElementById('nombre');
                    const emailInput = document.getElementById('email');
                    const nochesInput = document.getElementById('noches');

                    document.getElementById('reservationForm').style.display = 'block';
                    document.getElementById('successMsg').style.display = 'none';

                    if (reservationMode === 'cancel') {
                        document.getElementById('selectedDate').textContent = `Fecha reservada: ${day} de ${monthNames[month]} de ${year}. Presiona el boton para cancelar.`;
                        confirmBtn.style.display = 'none';
                        cancelBtn.style.display = 'inline-block';
                        nombreInput.style.display = 'none';
                        emailInput.style.display = 'none';
                        nochesInput.style.display = 'none';
                        nombreInput.removeAttribute('required');
                        emailInput.removeAttribute('required');
                        nochesInput.removeAttribute('required');
                    } else {
                        document.getElementById('selectedDate').textContent = `Fecha seleccionada: ${day} de ${monthNames[month]} de ${year}`;
                        confirmBtn.style.display = 'inline-block';
                        cancelBtn.style.display = 'none';
                        nombreInput.style.display = 'block';
                        emailInput.style.display = 'block';
                        nochesInput.style.display = 'block';
                        nombreInput.setAttribute('required', 'required');
                        emailInput.setAttribute('required', 'required');
                        nochesInput.setAttribute('required', 'required');
                    }
                });
                calendarGrid.appendChild(cell);
            }
        }

        document.getElementById('prevMonth').onclick = function () {
            currentMonth--;
            if (currentMonth < 0) {
                currentMonth = 11;
                currentYear--;
            }
            renderCalendar(currentMonth, currentYear);
            document.getElementById('reservationForm').style.display = 'none';
        };
        document.getElementById('nextMonth').onclick = function () {
            currentMonth++;
            if (currentMonth > 11) {
                currentMonth = 0;
                currentYear++;
            }
            renderCalendar(currentMonth, currentYear);
            document.getElementById('reservationForm').style.display = 'none';
        };

        function getCookieValue(name) {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop().split(';').shift();
            return '';
        }

        function tryAutoFillEmail() {
            const savedEmail = getCookieValue('lh_email');
            if (savedEmail) {
                const emailInput = document.getElementById('email');
                if (emailInput && !emailInput.value.trim()) {
                    emailInput.value = savedEmail;
                }
            }
        }

        document.getElementById('confirmReservation').onclick = async function () {
            const nombre = document.getElementById('nombre').value.trim();
            const email = document.getElementById('email').value.trim();
            const noches = parseInt(document.getElementById('noches').value, 10);
            if (!Number.isInteger(noches) || noches < 1 || noches > 30) {
                alert('La cantidad de noches debe estar entre 1 y 30.');
                return;
            }

            if (nombre && email && selectedDate) {
                try {
                    const response = await fetch(API_URL, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ fecha: selectedDate, nombre, email, noches })
                    });
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }
                    const data = await response.json();
                    if (data.success) {
                        await renderCalendar(currentMonth, currentYear);
                        document.getElementById('reservationForm').style.display = 'block';
                        document.getElementById('successMsg').textContent = noches > 1 ? `Reserva realizada con exito (${noches} noches)` : 'Reserva realizada con exito';
                        document.getElementById('successMsg').style.display = 'block';
                        document.getElementById('nombre').value = '';
                        document.getElementById('email').value = '';
                        document.getElementById('noches').value = '1';
                    } else {
                        alert(data.message || 'Error al realizar la reserva');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert(error.message || 'Error de conexión al servidor');
                }
            }
        };

        document.getElementById('cancelReservation').onclick = async function () {
            if (!selectedDate) {
                alert('Debes seleccionar una fecha reservada.');
                return;
            }

            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'cancel', fecha: selectedDate })
                });
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const data = await response.json();
                if (data.success) {
                    await renderCalendar(currentMonth, currentYear);
                    document.getElementById('reservationForm').style.display = 'block';
                    document.getElementById('successMsg').textContent = 'Reserva cancelada con exito';
                    document.getElementById('successMsg').style.display = 'block';
                    document.getElementById('email').value = '';
                    document.getElementById('nombre').value = '';
                    document.getElementById('cancelReservation').style.display = 'none';
                    document.getElementById('confirmReservation').style.display = 'inline-block';
                    document.getElementById('nombre').style.display = 'block';
                    document.getElementById('email').style.display = 'block';
                    document.getElementById('noches').style.display = 'block';
                    document.getElementById('nombre').setAttribute('required', 'required');
                    document.getElementById('email').setAttribute('required', 'required');
                    document.getElementById('noches').setAttribute('required', 'required');
                    document.getElementById('noches').value = '1';
                } else {
                    alert(data.message || 'No se pudo cancelar la reserva');
                }
            } catch (error) {
                console.error('Error:', error);
                alert(error.message || 'Error de conexión al servidor');
            }
        };

        tryAutoFillEmail();
        renderCalendar(currentMonth, currentYear);
    </script>
<?php include __DIR__ . '/includes/page-end.php'; ?>






