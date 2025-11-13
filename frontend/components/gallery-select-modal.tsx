"use client"

import { useState, useEffect } from "react"
import { apiClient } from "@/lib/api-client"
import { Plus, X } from "lucide-react"

interface MediaFile {
  id: number
  name: string
  file_path: string
  size: number
  mime_type: string
}

interface GallerySelectModalProps {
  albumId: string
  isOpen: boolean
  onClose: () => void
  onAddFiles: (fileIds: number[]) => void
  userId: string
}

export function GallerySelectModal({
  albumId,
  isOpen,
  onClose,
  onAddFiles,
  userId
}: GallerySelectModalProps) {
  const [mediaFiles, setMediaFiles] = useState<MediaFile[]>([])
  const [selectedIds, setSelectedIds] = useState<Set<number>>(new Set())
  const [loading, setLoading] = useState(false)
  const [page, setPage] = useState(1)
  const [hasMore, setHasMore] = useState(false)

  useEffect(() => {
    if (isOpen) {
      fetchMediaFiles(1)
    }
  }, [isOpen])

  const fetchMediaFiles = async (pageNum: number) => {
    setLoading(true)
    try {
      const data = await apiClient.get(
        `/media-files?user_id=${userId}&is_deleted=false&per_page=24&page=${pageNum}`
      )

      const files = Array.isArray(data) ? data : data.data || []

      if (pageNum === 1) {
        setMediaFiles(files)
      } else {
        setMediaFiles(prev => [...prev, ...files])
      }

      setHasMore(files.length === 24)
      setPage(pageNum)
    } catch (error) {
      console.error("Failed to fetch media:", error)
    } finally {
      setLoading(false)
    }
  }

  const toggleFile = (fileId: number) => {
    const newSelected = new Set(selectedIds)
    if (newSelected.has(fileId)) {
      newSelected.delete(fileId)
    } else {
      newSelected.add(fileId)
    }
    setSelectedIds(newSelected)
  }

  const handleSelectAll = () => {
    if (selectedIds.size === mediaFiles.length) {
      setSelectedIds(new Set())
    } else {
      setSelectedIds(new Set(mediaFiles.map(f => f.id)))
    }
  }

  const handleAddFiles = () => {
    if (selectedIds.size === 0) {
      alert("Please select at least one photo")
      return
    }
    onAddFiles(Array.from(selectedIds))
    setSelectedIds(new Set())
    onClose()
  }

  if (!isOpen) return null

  return (
    <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
      <div className="bg-card rounded-lg w-full max-w-4xl max-h-[90vh] flex flex-col border border-border shadow-xl">
        {/* Header */}
        <div className="flex items-center justify-between p-6 border-b border-border">
          <h2 className="text-2xl font-bold">📸 Select Photos to Add</h2>
          <button
            onClick={onClose}
            className="p-2 hover:bg-accent rounded-lg transition"
          >
            <X className="h-5 w-5" />
          </button>
        </div>

        {/* Selection Info */}
        <div className="px-6 py-3 bg-primary/5 border-b border-border flex items-center justify-between">
          <label className="flex items-center gap-2 cursor-pointer">
            <input
              type="checkbox"
              checked={selectedIds.size === mediaFiles.length && mediaFiles.length > 0}
              onChange={handleSelectAll}
              className="w-5 h-5 rounded border-border accent-primary"
            />
            <span className="text-sm font-medium">
              {selectedIds.size > 0 ? `${selectedIds.size} selected` : "Select all"}
            </span>
          </label>
        </div>

        {/* Gallery Grid */}
        <div className="flex-1 overflow-y-auto p-6">
          {loading && mediaFiles.length === 0 ? (
            <div className="flex items-center justify-center h-full">
              <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
            </div>
          ) : mediaFiles.length === 0 ? (
            <div className="text-center py-12">
              <p className="text-muted-foreground">No photos available</p>
            </div>
          ) : (
            <>
              <div className="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-4 mb-6">
                {mediaFiles.map((file) => (
                  <div
                    key={file.id}
                    className={`relative group cursor-pointer rounded-lg overflow-hidden border-2 transition ${selectedIds.has(file.id)
                        ? "border-primary bg-primary/10"
                        : "border-border hover:border-primary"
                      }`}
                    onClick={() => toggleFile(file.id)}
                  >
                    <img
                      src={`${process.env.NEXT_PUBLIC_API_URL}/storage/${file.file_path}`}
                      alt={file.name}
                      className="w-full h-24 object-cover"
                      onError={(e) => {
                        const img = e.target as HTMLImageElement
                        img.src = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Crect fill='%23f0f0f0' width='100' height='100'/%3E%3C/svg%3E"
                      }}
                    />
                    <div className="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition flex items-center justify-center">
                      <div
                        className={`w-6 h-6 rounded border-2 flex items-center justify-center transition ${selectedIds.has(file.id)
                            ? "bg-primary border-primary"
                            : "bg-white border-white"
                          }`}
                      >
                        {selectedIds.has(file.id) && (
                          <svg className="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                          </svg>
                        )}
                      </div>
                    </div>
                  </div>
                ))}
              </div>

              {hasMore && (
                <div className="flex justify-center">
                  <button
                    onClick={() => fetchMediaFiles(page + 1)}
                    className="px-4 py-2 border border-border rounded-lg hover:bg-accent transition"
                  >
                    Load More
                  </button>
                </div>
              )}
            </>
          )}
        </div>

        {/* Footer */}
        <div className="border-t border-border p-6 flex justify-end gap-3">
          <button
            onClick={onClose}
            className="px-6 py-2 border border-border rounded-lg hover:bg-accent transition"
          >
            Cancel
          </button>
          <button
            onClick={handleAddFiles}
            disabled={selectedIds.size === 0 || loading}
            className="inline-flex items-center gap-2 px-6 py-2 bg-primary text-white rounded-lg hover:opacity-90 transition disabled:opacity-50"
          >
            <Plus className="h-4 w-4" />
            Add ({selectedIds.size})
          </button>
        </div>
      </div>
    </div>
  )
}
