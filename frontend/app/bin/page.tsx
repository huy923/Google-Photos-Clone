"use client"
import { useState, useEffect } from "react"
import { useRouter } from "next/navigation"
import Image from "next/image"
import { apiClient } from "@/lib/api-client"
import { Button } from "@/components/ui/button"
import { Trash2 } from "lucide-react"

interface DeletedMediaFile {
    id: number
    user_id: string
    original_name: string
    filename: string
    file_path: string
    mime_type: string
    file_type: 'image' | 'video' | 'audio' | 'document' | 'folder'
    file_size: number
    width: number | null
    height: number | null
    is_deleted: boolean
    deleted_at: string
    created_at: string
}

export default function BinPage() {
    const router = useRouter()
    const [userId, setUserId] = useState<string | null>(null)
    const [deletedFiles, setDeletedFiles] = useState<DeletedMediaFile[]>([])
    const [loading, setLoading] = useState(true)
    const [selectedFile, setSelectedFile] = useState<DeletedMediaFile | null>(null)
    const [selectedIds, setSelectedIds] = useState<Set<number>>(new Set())

    useEffect(() => {
        const id = localStorage.getItem("userId")
        if (!id) {
            router.push("/login")
        } else {
            setUserId(id)
            fetchDeletedFiles(id)
        }
    }, [router])

    const fetchDeletedFiles = async (userId: string) => {
        try {
            const data = await apiClient.get(`/media-files?user_id=${userId}&is_deleted=true`)
            setDeletedFiles(Array.isArray(data) ? data : data.data || [])
        } catch (error) {
            console.error("Failed to fetch deleted files:", error)
        } finally {
            setLoading(false)
        }
    }

    const handleRestore = async (id: number) => {
        try {
            await apiClient.patch(`/media-files/${id}`, { is_deleted: false })
            setDeletedFiles(deletedFiles.filter((f) => f.id !== id))
            alert("File restored successfully!")
        } catch (error) {
            console.error("Failed to restore file:", error)
            alert("Failed to restore file")
        }
    }

    const handlePermanentDelete = async (id: number) => {
        if (!confirm("Are you sure you want to permanently delete this file?")) return

        try {
            await apiClient.delete(`/media-files/${id}`)
            setDeletedFiles(deletedFiles.filter((f) => f.id !== id))
            setSelectedFile(null)
            alert("File deleted permanently!")
        } catch (error) {
            console.error("Failed to permanently delete file:", error)
            alert("Failed to delete file")
        }
    }

    const handleDeleteMultiple = async () => {
        if (selectedIds.size === 0) {
            alert("Please select files to delete")
            return
        }

        if (!confirm(`Delete ${selectedIds.size} file(s) permanently? Cannot recover.`)) return

        try {
            for (const id of selectedIds) {
                await apiClient.delete(`/media-files/${id}`)
            }
            setDeletedFiles(deletedFiles.filter((f) => !selectedIds.has(f.id)))
            setSelectedIds(new Set())
            alert("Files deleted permanently!")
        } catch (error) {
            console.error("Failed to delete files:", error)
            alert("Failed to delete files")
        }
    }

    const handleRestoreMultiple = async () => {
        if (selectedIds.size === 0) {
            alert("Please select files to restore")
            return
        }

        try {
            for (const id of selectedIds) {
                await apiClient.patch(`/media-files/${id}`, { is_deleted: false })
            }
            setDeletedFiles(deletedFiles.filter((f) => !selectedIds.has(f.id)))
            setSelectedIds(new Set())
            alert("Files restored successfully!")
        } catch (error) {
            console.error("Failed to restore files:", error)
            alert("Failed to restore files")
        }
    }

    const toggleFileSelection = (id: number) => {
        const newSelected = new Set(selectedIds)
        if (newSelected.has(id)) {
            newSelected.delete(id)
        } else {
            newSelected.add(id)
        }
        setSelectedIds(newSelected)
    }

    const selectAll = () => {
        if (selectedIds.size === deletedFiles.length) {
            setSelectedIds(new Set())
        } else {
            setSelectedIds(new Set(deletedFiles.map((f) => f.id)))
        }
    }

    const getMediaPreview = (item: DeletedMediaFile) => {
        const api = process.env.NEXT_PUBLIC_API_URL
        const imageUrl = api + `/storage/${item.file_path}`

        if (item.file_type === 'image') {
            return (
                <Image
                    src={imageUrl}
                    alt={item.original_name}
                    fill
                    className="object-cover group-hover:scale-105 transition-transform"
                    unoptimized={true}
                />
            )
        } else if (item.file_type === 'video') {
            return (
                <div className="w-full h-full flex items-center justify-center">
                    <video
                        className="max-h-full max-w-full"
                        src={imageUrl}
                        playsInline
                    />
                </div>
            )
        } else if (item.file_type === 'folder') {
            return (
                <div className="w-full h-full flex items-center justify-center bg-gray-100">
                    <div className="text-center p-4">
                        <div className="text-4xl mb-2">📁</div>
                        <p className="text-sm text-muted-foreground truncate">{item.original_name}</p>
                    </div>
                </div>
            )
        }
        else {
            return (
                <div className="w-full h-full flex items-center justify-center bg-gray-100">
                    <div className="text-center p-4">
                        <div className="text-4xl mb-2">📄</div>
                        <p className="text-sm text-muted-foreground truncate">{item.original_name}</p>
                    </div>
                </div>
            )
        }
    }

    if (loading) {
        return (
            <div className="flex items-center justify-center py-12">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
            </div>
        )
    }

    if (deletedFiles.length === 0) {
        return (
            <div className="text-center py-12">
                <p className="text-lg text-muted-foreground">No files in the bin</p>
            </div>
        )
    }

    const api = process.env.NEXT_PUBLIC_API_URL

    return (
        <div className="w-full mt-2">
            <div className="pb-4 flex items-center justify-between border-b mb-4">
                <h1 className="text-3xl font-bold ml-3 flex items-center"><Trash2 className="mr-2" /> Bin</h1>
                <div className="flex items-center gap-3 mr-3">
                    <p className="text-sm text-muted-foreground">{deletedFiles.length} file{deletedFiles.length !== 1 ? 's' : ''}</p>
                    {selectedIds.size > 0 && (
                        <div className="flex gap-2 justify-center items-center">
                            <p className="text-sm font-medium ">{selectedIds.size} selected</p>
                            <Button
                                onClick={handleRestoreMultiple}
                                variant="default"
                                size="sm"
                            >
                                ↩️ Restore
                            </Button>
                            <Button
                                onClick={handleDeleteMultiple}
                                variant="destructive"
                                size="sm"
                            >
                                🗑️ Delete
                            </Button>
                        </div>
                    )}
                </div>
            </div>

            {deletedFiles.length > 0 && selectedIds.size === 0 && (
                <div className="px-3 mb-4">
                    <Button
                        onClick={selectAll}
                        variant="outline"
                        size="sm"
                    >
                        Select All
                    </Button>
                </div>
            )}

            <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 m-3">
                {deletedFiles.map((item) => (
                    <div
                        key={item.id}
                        className={`group relative bg-card rounded-lg overflow-hidden border cursor-pointer transition ${selectedIds.has(item.id)
                                ? 'border-primary bg-primary/10'
                                : 'border-border'
                            }`}
                        onClick={() => toggleFileSelection(item.id)}
                    >
                        <div className="aspect-square relative">
                            {getMediaPreview(item)}
                            {selectedIds.has(item.id) && (
                                <div className="absolute inset-0 bg-primary/20 flex items-center justify-center">
                                    <div className="text-2xl">✓</div>
                                </div>
                            )}
                        </div>
                        <div className="absolute bottom-0 left-0 right-0 bg-black/50 opacity-0 group-hover:opacity-100 transition p-2">
                            <p className="text-white text-xs truncate">{item.original_name}</p>
                            <p className="text-gray-300 text-xs">
                                {new Date(item.deleted_at).toLocaleDateString()}
                            </p>
                        </div>
                    </div>
                ))}
            </div>

            {selectedFile && (
                <div
                    className="fixed inset-0 flex items-center justify-center z-50 p-4 bg-black/50"
                    onClick={() => setSelectedFile(null)}
                >
                    <div
                        className="bg-card rounded-lg max-w-4xl w-full max-h-[90vh] flex flex-col"
                        onClick={(e) => e.stopPropagation()}
                    >
                        <div className="relative flex-1 flex items-center justify-center p-4">
                            {selectedFile.file_type === 'image' ? (
                                <Image
                                    src={`${api}/storage/${selectedFile.file_path}`}
                                    alt={selectedFile.original_name}
                                    width={selectedFile.width || 800}
                                    height={selectedFile.height || 600}
                                    className="max-w-full max-h-[70vh] object-contain"
                                    unoptimized={true}
                                />
                            ) : selectedFile.file_type === 'video' ? (
                                <video
                                    controls
                                    className="max-w-full max-h-[70vh]"
                                    src={`${api}/storage/${selectedFile.file_path}`}
                                >
                                    Your browser does not support the video tag.
                                </video>
                            ) : (
                                <div className="text-center p-8">
                                    <div className="text-6xl mb-4">📄</div>
                                    <p className="text-lg font-medium">{selectedFile.original_name}</p>
                                </div>
                            )}
                        </div>

                        <div className="p-4 border-t border-border">
                            <div className="flex justify-between items-center">
                                <div>
                                    <p className="font-medium">{selectedFile.original_name}</p>
                                    <p className="text-sm text-muted-foreground">
                                        Time: {new Date(selectedFile.deleted_at).toLocaleString()}
                                    </p>
                                </div>
                                <div className="flex gap-2">
                                    <Button
                                        onClick={() => {
                                            handleRestore(selectedFile.id)
                                            setSelectedFile(null)
                                        }}
                                        variant="default"
                                    >
                                        ↩️ Restore
                                    </Button>
                                    <Button
                                        onClick={() => handlePermanentDelete(selectedFile.id)}
                                        variant="destructive"
                                    >
                                        🗑️ Remove
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </div>
    )
}