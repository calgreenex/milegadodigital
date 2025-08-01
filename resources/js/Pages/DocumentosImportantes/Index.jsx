import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function Index({ auth, documentos }) {
    const { delete: destroy } = useForm();
    const { props } = usePage();
    const { plan, resource_counts } = props.auth.user;

    const canUploadDocument = plan.features.max_documents === '-1' || resource_counts.documents < plan.features.max_documents;

    const handleDelete = (id) => {
        if (confirm('¿Estás seguro de que quieres eliminar este documento?')) {
            destroy(route('documentos-importantes.destroy', id));
        }
    };

    const [showHelp, setShowHelp] = useState(false);

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Documentos Importantes</h2>}
        >
            <Head title="Documentos Importantes" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            <button
                                onClick={() => setShowHelp(!showHelp)}
                                className="mb-4 px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-opacity-75"
                            >
                                {showHelp ? 'Ocultar Ayuda' : 'Mostrar Ayuda'}
                            </button>

                            {showHelp && (
                                <div className="mb-4 p-4 bg-blue-100 border border-blue-200 text-blue-800 rounded-lg">
                                    <h3 className="font-bold text-lg mb-2">Ayuda: Documentos Importantes</h3>
                                    <p className="mb-2">
                                        Aquí puedes subir y gestionar tus documentos importantes de forma segura.
                                    </p>
                                    <ul className="list-disc list-inside">
                                        <li><strong>Subir Nuevo Documento:</strong> Permite cargar archivos importantes, categorizarlos y definir su nivel de acceso.</li>
                                        <li><strong>Descargar:</strong> Descarga una copia de tus documentos.</li>
                                        <li><strong>Editar:</strong> Modifica los detalles de un documento existente.</li>
                                        <li><strong>Eliminar:</strong> Borra un documento de forma permanente.</li>
                                        <li><strong>Nivel de Acceso:</strong> Controla quién puede acceder a tus documentos (privado, contactos de confianza, o público).</li>
                                    </ul>
                                </div>
                            )}

                            <div className="flex justify-end mb-4">
                                <Link
                                    href={route('documentos-importantes.create')}
                                    className={`bg-blue-500 text-white font-bold py-2 px-4 rounded ${
                                        !canUploadDocument ? 'opacity-50 cursor-not-allowed' : 'hover:bg-blue-700'
                                    }`}
                                    disabled={!canUploadDocument}
                                >
                                    Subir Nuevo Documento
                                </Link>
                            </div>

                            {!canUploadDocument && (
                                <div className="mb-4 p-4 bg-yellow-100 text-yellow-800 border border-yellow-300 rounded-lg">
                                    Has alcanzado el límite de documentos para tu plan actual ({plan.name}). Para subir más, considera
                                    actualizar tu plan.
                                </div>
                            )}

                            {documentos.length === 0 ? (
                                <p>No tienes documentos importantes subidos.</p>
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="min-w-full bg-white">
                                        <thead>
                                            <tr>
                                                <th className="py-2 px-4 border-b">Título</th>
                                                <th className="py-2 px-4 border-b">Categoría</th>
                                                <th className="py-2 px-4 border-b">Tipo de Archivo</th>
                                                <th className="py-2 px-4 border-b">Tamaño</th>
                                                <th className="py-2 px-4 border-b">Nivel de Acceso</th>
                                                <th className="py-2 px-4 border-b">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {documentos.map((documento) => (
                                                <tr key={documento.id}>
                                                    <td className="py-2 px-4 border-b">{documento.titulo}</td>
                                                    <td className="py-2 px-4 border-b">{documento.categoria}</td>
                                                    <td className="py-2 px-4 border-b">{documento.tipo_archivo}</td>
                                                    <td className="py-2 px-4 border-b">{(documento.tamano_archivo / 1024 / 1024).toFixed(2)} MB</td>
                                                    <td className="py-2 px-4 border-b">{documento.nivel_acceso}</td>
                                                    <td className="py-2 px-4 border-b">
                                                        <a
                                                            href={route('documentos-importantes.download', documento.id)}
                                                            className="bg-green-500 hover:bg-green-700 text-white font-bold py-1 px-2 rounded mr-2"
                                                        >
                                                            Descargar
                                                        </a>
                                                        <Link
                                                            href={route('documentos-importantes.edit', documento.id)}
                                                            className="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-1 px-2 rounded mr-2"
                                                        >
                                                            Editar
                                                        </Link>
                                                        <button
                                                            onClick={() => handleDelete(documento.id)}
                                                            className="bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-2 rounded"
                                                        >
                                                            Eliminar
                                                        </button>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
