"use client"

import { useState, useEffect } from "react"
import Image from "next/image"
import { apiClient } from "@/lib/api-client"
import { Button } from "./ui/button"
import { ShareIcon, Trash, ChevronUp, ChevronDown, ArrowDownNarrowWideIcon, ClockArrowUp, CalendarRangeIcon, CalendarX, CalendarDays } from "lucide-react"

interface MediaFile {
  id: number
  user_id: string
  original_name: string
  filename: string
  file_path: string
  thumbnail_path: string | null
  mime_type: string
  file_type: 'image' | 'video' | 'audio' | 'document' | 'folder'
  file_size: number
  width: number | null
  height: number | null
  duration: number | null
  is_processed: boolean
  is_optimized: boolean
  is_deleted: boolean
  created_at: string
  updated_at: string
}

interface PaginatedResponse {
  data: MediaFile[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}

interface GalleryGridProps {
  userId: string;
  className?: string;
}

type SortBy = 'newest' | 'oldest' | 'name-asc' | 'name-desc' | 'size-asc' | 'size-desc'

export function GalleryGrid({ userId }: GalleryGridProps) {
  const [media, setMedia] = useState<MediaFile[]>([])
  const [loading, setLoading] = useState(true)
  const [selectedMedia, setSelectedMedia] = useState<MediaFile | null>(null)
  const [currentPage, setCurrentPage] = useState(1)
  const [totalPages, setTotalPages] = useState(1)
  const [perPage, setPerPage] = useState(12)
  const [sortBy, setSortBy] = useState<SortBy>('newest')
  const [totalItems, setTotalItems] = useState(0)
  const [groupByDate, setGroupByDate] = useState(true)

  useEffect(() => {
    fetchMedia()
  }, [userId, currentPage, perPage, sortBy])

  const fetchMedia = async () => {
    setLoading(true)
    try {
      const data = await apiClient.get(
        `/media-files?user_id=${userId}&page=${currentPage}&per_page=${perPage}`
      )

      let sortedData = Array.isArray(data) ? data : data.data || []

      // Client-side sorting
      sortedData = sortMediaFiles(sortedData, sortBy)

      setMedia(sortedData)

      // Update pagination info
      if (data.last_page) {
        setTotalPages(data.last_page)
        setTotalItems(data.total)
      }
    } catch (error) {
      console.error("Failed to fetch media:", error)
    } finally {
      setLoading(false)
    }
  }

  const sortMediaFiles = (files: MediaFile[], sortType: SortBy): MediaFile[] => {
    const sorted = [...files]

    switch (sortType) {
      case 'newest':
        return sorted.sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime())
      case 'oldest':
        return sorted.sort((a, b) => new Date(a.created_at).getTime() - new Date(b.created_at).getTime())
      case 'name-asc':
        return sorted.sort((a, b) => a.original_name.localeCompare(b.original_name))
      case 'name-desc':
        return sorted.sort((a, b) => b.original_name.localeCompare(a.original_name))
      case 'size-asc':
        return sorted.sort((a, b) => a.file_size - b.file_size)
      case 'size-desc':
        return sorted.sort((a, b) => b.file_size - a.file_size)
      default:
        return sorted
    }
  }

  // Group files by upload date
  const groupFilesByDate = (files: MediaFile[]): Record<string, MediaFile[]> => {
    const grouped: Record<string, MediaFile[]> = {}

    files.forEach((file) => {
      const date = new Date(file.created_at)
      const today = new Date()
      const yesterday = new Date(today)
      yesterday.setDate(yesterday.getDate() - 1)

      let dateKey: string

      // Check if today or yesterday
      if (date.toDateString() === today.toDateString()) {
        dateKey = 'Today'
      } else if (date.toDateString() === yesterday.toDateString()) {
        dateKey = 'Yesterday'
      } else {
        // Format as "Monday, November 11, 2025"
        dateKey = date.toLocaleDateString('en-US', {
          weekday: 'long',
          year: 'numeric',
          month: 'long',
          day: 'numeric'
        })
      }

      if (!grouped[dateKey]) {
        grouped[dateKey] = []
      }
      grouped[dateKey].push(file)
    })

    return grouped
  }

  const handleDelete = async (id: number) => {
    if (!confirm("Are you sure you want to delete this file?")) return

    try {
      await apiClient.delete(`/media-files/${id}`)
      setMedia(media.filter((m) => m.id !== id))
      setSelectedMedia(null)
      alert("File deleted successfully!")
    } catch (error) {
      console.error("Failed to delete media:", error)
      alert("Failed to delete file. Please try again.")
    }
  }

  const handleShare = async (id: number) => {
    try {
      const shareableLink = `${window.location.origin}/media/${id}`;
      await navigator.clipboard.writeText(shareableLink);
      alert("Shareable link copied to clipboard!");
    } catch (error) {
      console.error("Failed to share media:", error);
      alert("Failed to copy shareable link.");
    }
  }
  if (loading) {
    return (
      <div className="flex items-center justify-center py-12">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
      </div>
    )
  }

  if (media.length === 0) {
    return (
      <div className="text-center py-12">
        <p className="">You don't have any media files yet. You can drop files here to upload them.</p>
      </div>
    )
  }

  const getMediaPreview = (item: MediaFile) => {
    const api = process.env.NEXT_PUBLIC_API_URL;
    const imageUrl = api + `/storage/${item.file_path}`;

    if (item.file_type === 'image') {
      return (
        <Image
          src={imageUrl}
          alt={item.original_name}
          loading="lazy"
          fill
          lazyRoot="#root"
          className="object-cover group-hover:scale-105 transition-transform"
          unoptimized={true}
        />
      );
    } else if (item.file_type === 'video') {
      return (
        <div className="w-full h-full flex items-center justify-center ">
          <video
            className="max-h-full max-w-full"
            src={imageUrl}
            controls
            aria-label={item.original_name}
            playsInline
          />
        </div>
      );
    } else if (item.file_type === 'folder') {
      return (
        <div className="w-full h-full flex items-center justify-center bg-gray-100">
          <div className="text-center p-4">
            <div className="text-4xl mb-2">📁</div>
            <p className="text-sm text-muted-foreground truncate">{item.original_name}</p>
          </div>
        </div>
      );
    }
    else {
      return (
        <div className="w-full h-full flex items-center justify-center bg-gray-100">
          <div className="text-center p-4">
            <div className="text-4xl mb-2">📄</div>
            <p className="text-sm text-muted-foreground truncate">{item.original_name}</p>
          </div>
        </div>
      );
    }
  };
  const api = process.env.NEXT_PUBLIC_API_URL;
  return (
    <>
      {/* Sorting and Pagination Controls */}
      <div className="bg-card border-b border-border p-2 sticky">
        <div className="flex items-center justify-between">
          {/* Sort Controls */}
          <div className="flex items-center gap-2">
            <Button
              onClick={() => setGroupByDate(!groupByDate)}
              variant={"outline"}
              className="text-sm"
            >
              {groupByDate ? <CalendarDays /> : <CalendarX />}
            </Button>
            <select
              value={sortBy}
              onChange={(e) => {
                setSortBy(e.target.value as SortBy)
                setCurrentPage(1)
              }}
              className="p-2 border border-border rounded-md text-sm"
            >
              <option value="newest">🕛 Newest</option>
              <option value="oldest">🕑 Oldest</option>
              <option value="name-asc">📝 Name A-Z</option>
              <option value="name-desc">📝 Name Z-A</option>
              <option value="size-asc">📦 Smallest Size</option>
              <option value="size-desc">📦 Largest Size</option>
            </select>
          </div>
          {/* Items per page */}
          <div className="flex items-center gap-2">
            <label className="text-sm font-medium hidden md:block">Show:</label>
            <select
              value={perPage}
              onChange={(e) => {
                setPerPage(Number(e.target.value))
                setCurrentPage(1)
              }}
              className="px-3 py-2 border border-border rounded-md text-sm "
            >
              <option value="6">6</option>
              <option value="12">12</option>
              <option value="24">24</option>
              <option value="48">48</option>
            </select>
            <span className="text-sm text-muted-foreground hidden md:block">
              per page ({totalItems} total)
            </span>
          </div>
        </div>
      </div>

      {/* Gallery Grid */}
      {groupByDate ? (
        // Grouped by date view
        <div className="space-y-8 m-3">
          {Object.entries(groupFilesByDate(media)).map(([dateGroup, files]) => (
            <div key={dateGroup}>
              <h2 className="text-xl font-bold mb-4 text-primary flex "><CalendarRangeIcon className="w-5 mr-1" /> {dateGroup}</h2>
              <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                {files.map((item) => (
                  <div key={item.id} className="group relative bg-card rounded-lg overflow-hidden border border-border">
                    <div className="aspect-square relative cursor-pointer" onClick={() => setSelectedMedia(item)}>
                      {getMediaPreview(item)}
                    </div>
                    <Button
                      onClick={(e) => {
                        e.stopPropagation();
                        handleDelete(item.id);
                      }}
                      className="absolute top-2 right-2 transition opacity-0 group-hover:opacity-100 cursor-pointer"
                      variant="secondary"
                      title="Delete"
                      size="sm"
                    >
                      <Trash className="h-4 w-4" />
                    </Button>
                    <Button
                      onClick={(e) => {
                        e.stopPropagation();
                        handleShare(item.id);
                      }}
                      className="absolute top-2 right-13 transition opacity-0 group-hover:opacity-100 cursor-pointer"
                      variant="secondary"
                      title="Share"
                      size="sm"
                    >
                      <ShareIcon className="h-4 w-4" />
                    </Button>
                  </div>
                ))}
              </div>
            </div>
          ))}
        </div>
      ) : (
        // Regular grid view
        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 m-3">
          {media.map((item) => (
            <div key={item.id} className="group relative bg-card rounded-lg overflow-hidden border border-border">
              <div className="aspect-square relative cursor-pointer " onClick={() => setSelectedMedia(item)}>
                {getMediaPreview(item)}
              </div>
              <Button
                onClick={(e) => {
                  e.stopPropagation();
                  handleDelete(item.id);
                }}
                className="absolute top-2 right-2 transition opacity-0 group-hover:opacity-100 cursor-pointer"
                variant="secondary"
                title="Delete"
                size="sm"
              >
                <Trash className="h-4 w-4" />
              </Button>
              <Button
                onClick={(e) => {
                  e.stopPropagation();
                  handleShare(item.id);
                }}
                className="absolute top-2 right-13 transition opacity-0 group-hover:opacity-100 cursor-pointer"
                variant="secondary"
                title="Share"
                size="sm"
              >
                <ShareIcon className="h-4 w-4" />
              </Button>
            </div>
          ))}
        </div>
      )}

      {/* Pagination Controls */}
      {totalPages > 1 && (
        <div className="flex items-center justify-center gap-4 mt-8 pb-8">
          <Button
            onClick={() => setCurrentPage(prev => Math.max(1, prev - 1))}
            disabled={currentPage === 1}
            variant="outline"
          >
            <ChevronUp className="h-4 w-4 mr-1" /> Trước
          </Button>

          <div className="flex items-center gap-2">
            {Array.from({ length: Math.min(5, totalPages) }, (_, i) => {
              const pageNum = i + 1
              return (
                <button
                  key={pageNum}
                  onClick={() => setCurrentPage(pageNum)}
                  className={`px-3 py-2 rounded-md text-sm ${currentPage === pageNum
                    ? 'bg-primary text-primary-foreground'
                    : 'border border-border hover:bg-accent'
                    }`}
                >
                  {pageNum}
                </button>
              )
            })}
            {totalPages > 5 && (
              <span className="px-2 text-muted-foreground">...</span>
            )}
          </div>

          <span className="text-sm text-muted-foreground">
            Trang {currentPage} / {totalPages}
          </span>

          <Button
            onClick={() => setCurrentPage(prev => Math.min(totalPages, prev + 1))}
            disabled={currentPage === totalPages}
            variant="outline"
          >
            Sau <ChevronDown className="h-4 w-4 ml-1" />
          </Button>
        </div>
      )}
      {/* Modal View */}
      {selectedMedia && (
        <div
          className="fixed inset-0 flex items-center justify-center z-50 p-4"
          onClick={() => setSelectedMedia(null)}
        >
          <div className="bg-card rounded-lg max-w-4xl w-full max-h-[90vh] flex flex-col" onClick={(e) => e.stopPropagation()}>
            <div className="relative flex-1 flex items-center justify-center p-4">
              {selectedMedia.file_type === 'image' ? (
                <Image
                  src={`${api}/storage/${selectedMedia.file_path}`}
                  alt={selectedMedia.original_name}
                  width={selectedMedia.width || 800}
                  height={selectedMedia.height || 600}
                  className="max-w-full max-h-[70vh] object-contain"
                  unoptimized={true}
                />
              ) : selectedMedia.file_type === 'video' ? (
                <video
                  controls
                  className="max-w-full max-h-[70vh]"
                  src={`${api}/storage/${selectedMedia.file_path}`}
                >
                  Your browser does not support the video tag.
                </video>
              ) : (
                <div className="text-center p-8">
                  <div className="text-6xl mb-4">📄</div>
                  <p className="text-lg font-medium">{selectedMedia.original_name}</p>
                  <p className="text-sm text-muted-foreground mt-2">
                    {Math.round(selectedMedia.file_size / 1024)} KB • {selectedMedia.mime_type}
                  </p>
                </div>
              )}
            </div>
            <div className="p-4 border-t border-border">
              <div className="flex justify-between items-center">
                <div>
                  <p className="font-medium">{selectedMedia.original_name}</p>
                  <p className="text-sm text-muted-foreground">
                    {selectedMedia.width && selectedMedia.height
                      ? `${selectedMedia.width} × ${selectedMedia.height}`
                      : ''} • {new Date(selectedMedia.created_at).toLocaleDateString()}
                  </p>
                </div>
                <Button
                  onClick={() => {
                    handleDelete(selectedMedia.id);
                  }}
                  variant={"destructive"}
                  title="Delete"
                >
                  <Trash></Trash>
                </Button>
              </div>
            </div>
          </div>
        </div>
      )}
    </>
  )
}
