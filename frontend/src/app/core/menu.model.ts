export interface Plato {
  id: number;
  categoria_id: number;
  nombre: string;
  descripcion: string | null;
  precio: number;
  imagen_url: string | null;
  disponible: boolean;
}

export interface Categoria {
  id: number;
  nombre: string;
  orden: number;
  activa: boolean;
  platos: Plato[];
}
