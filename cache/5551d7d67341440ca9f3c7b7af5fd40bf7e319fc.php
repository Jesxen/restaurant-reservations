

<?php $__env->startSection('title', 'Listado de reservas'); ?>

<?php $__env->startSection('content'); ?>
<div>
  <h2 class="text-3xl font-bold mb-6 text-gray-900">Reservas realizadas</h2>
  <p class="text-gray-600 mb-6">Aquí puedes consultar todas las reservas registradas en el sistema.</p>

  <div class="overflow-x-auto">
    <table class="w-full text-left bg-white border border-gray-200 rounded-xl shadow-sm">
      <thead class="bg-gray-100">
        <tr>
          <th class="px-4 py-3 font-semibold text-gray-700"> Usuario</th>
          <th class="px-4 py-3 font-semibold text-gray-700"> Fecha</th>
          <th class="px-4 py-3 font-semibold text-gray-700"> Hora</th>
          <th class="px-4 py-3 font-semibold text-gray-700"> Personas</th>
        </tr>
      </thead>
      <tbody>
        <?php $__currentLoopData = $reservas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <tr class="hover:bg-gray-50 transition">
          <td class="px-4 py-3 border-t border-gray-200"><?php echo e($r['usuario']); ?></td>
          <td class="px-4 py-3 border-t border-gray-200"><?php echo e($r['fecha']); ?></td>
          <td class="px-4 py-3 border-t border-gray-200"><?php echo e($r['hora']); ?></td>
          <td class="px-4 py-3 border-t border-gray-200"><?php echo e($r['personas']); ?></td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </tbody>
    </table>
  </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layout', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Apache24\htdocs\restaurant-app\views/listado.blade.php ENDPATH**/ ?>