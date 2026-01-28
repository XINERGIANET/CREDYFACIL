@extends('template.app')

@section('title', 'Sitio en Construcción')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8 text-center">
        <div class="construction-container">
            <!-- Animación SVG -->
            <svg xmlns="http://www.w3.org/2000/svg" width="150" height="150" viewBox="0 0 24 24" fill="none" stroke="#f39c12" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-tool">
                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
            </svg>
            
            <h1 class="construction-title">¡Estamos trabajando en esto!</h1>
            <p class="construction-text">Nuestro equipo está desarrollando esta sección con las mejores herramientas y tecnología.</p>
            
            <div class="countdown-container">
                <p>Disponible en:</p>
                <div id="countdown" class="countdown-timer">
                    <div><span id="days">00</span><small>Días</small></div>
                    <div><span id="hours">00</span><small>Horas</small></div>
                    <div><span id="minutes">00</span><small>Min</small></div>
                    <div><span id="seconds">00</span><small>Seg</small></div>
                </div>
            </div>
            
            <div class="progress mt-4">
                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 75%"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    .construction-container {
        padding: 3rem 2rem;
        background: #fff;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        margin-top: 2rem;
    }
    
    .construction-title {
        color: #2c3e50;
        font-weight: 700;
        margin: 1.5rem 0;
        font-size: 2.2rem;
    }
    
    .construction-text {
        color: #7f8c8d;
        font-size: 1.2rem;
        margin-bottom: 2rem;
    }
    
    .countdown-container {
        margin: 2rem 0;
    }
    
    .countdown-timer {
        display: flex;
        justify-content: center;
        gap: 1.5rem;
        margin-top: 1rem;
    }
    
    .countdown-timer div {
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 10px;
        min-width: 80px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.05);
    }
    
    .countdown-timer span {
        font-size: 2rem;
        font-weight: 700;
        color: #e74c3c;
        display: block;
    }
    
    .countdown-timer small {
        color: #95a5a6;
        font-size: 0.8rem;
        text-transform: uppercase;
    }
    
    .progress {
        height: 10px;
        border-radius: 5px;
        background: #ecf0f1;
    }
    
    .progress-bar {
        background-color: #3498db;
    }
    
    .contact-info {
        margin-top: 2rem;
        padding-top: 1.5rem;
        border-top: 1px solid #eee;
    }
    
    .feather-tool {
        animation: bounce 2s infinite;
    }
    
    @keyframes bounce {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-15px); }
    }
</style>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Fecha objetivo (ajusta según necesites)
        const countDownDate = new Date();
        countDownDate.setDate(countDownDate.getDate() + 30); // 7 días desde hoy
        
        // Actualizar el contador cada segundo
        const x = setInterval(function() {
            const now = new Date().getTime();
            const distance = countDownDate - now;
            
            // Cálculos de tiempo
            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            // Mostrar resultado
            $('#days').text(days.toString().padStart(2, '0'));
            $('#hours').text(hours.toString().padStart(2, '0'));
            $('#minutes').text(minutes.toString().padStart(2, '0'));
            $('#seconds').text(seconds.toString().padStart(2, '0'));
            
            // Si el contador termina
            if (distance < 0) {
                clearInterval(x);
                $('#countdown').html('<div class="text-success">¡Pronto estará listo!</div>');
            }
        }, 1000);
    });
</script>
@endsection