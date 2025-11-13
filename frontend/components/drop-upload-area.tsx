"use client";
import { useState, useRef, useEffect } from "react";
import { motion, AnimatePresence } from "framer-motion";

declare global {
  interface DataTransferItem {
    webkitGetAsEntry(): FileSystemEntry | null;
  }

  interface FileSystemEntry {
    readonly isFile: boolean;
    readonly isDirectory: boolean;
    readonly name: string;
    readonly fullPath: string;
  }

  interface FileSystemFileEntry extends FileSystemEntry {
    file(
      successCallback: (file: File) => void,
      errorCallback?: (error: DOMException) => void
    ): void;
  }

  interface FileSystemDirectoryEntry extends FileSystemEntry {
    createReader(): FileSystemDirectoryReader;
  }

  interface FileSystemDirectoryReader {
    readEntries(
      successCallback: (entries: FileSystemEntry[]) => void,
      errorCallback?: (error: DOMException) => void
    ): void;
  }

}
interface DropUploadAreaProps {
  userId: string;
  onUploadSuccess?: () => void;
  className?: string;
}

export default function DropUploadArea({ userId, onUploadSuccess }: DropUploadAreaProps) {
  const [isDragging, setIsDragging] = useState(false);
  const [uploading, setUploading] = useState(false);
  const [showOverlay, setShowOverlay] = useState(false);

  // Global dragover/drop events
  useEffect(() => {
    const onDragOver = (e: DragEvent) => {
      e.preventDefault();
      setIsDragging(true);
      setShowOverlay(true);
    };
    const onDrop = async (e: DragEvent) => {
      e.preventDefault();
      setIsDragging(false);
      setShowOverlay(false);
      if (!userId) return;

      const items = e.dataTransfer?.items;
      if (!items || items.length === 0) return;

      // Process all items (files and directories)
      const filePromises = [];
      for (let i = 0; i < items.length; i++) {
        const item = items[i];
        if (item.kind === 'file') {
          filePromises.push(processItem(item));
        }
      }

      try {
        const files = await Promise.all(filePromises);
        const flattenedFiles = files.flat();
        if (flattenedFiles.length > 0) {
          await uploadFiles(flattenedFiles);
        }
      } catch (err) {
        console.error('Error processing files:', err);
        alert('Error processing files. Please check console for details.');
      }
    };
    const onDragLeave = (e: DragEvent) => {
      setIsDragging(false);
      setShowOverlay(false);
    };
    window.addEventListener('dragover', onDragOver);
    window.addEventListener('drop', onDrop);
    window.addEventListener('dragleave', onDragLeave);
    return () => {
      window.removeEventListener('dragover', onDragOver);
      window.removeEventListener('drop', onDrop);
      window.removeEventListener('dragleave', onDragLeave);
    };
  }, [userId]);

  // Process a single item (file or directory)
  const processItem = async (item: DataTransferItem): Promise<File[]> => {
    if (!item.webkitGetAsEntry) return [];

    const entry = item.webkitGetAsEntry();
    if (!entry) return [];

    // Process directory
    if (entry.isDirectory) {
      return processDirectory(entry as FileSystemDirectoryEntry);
    }
    // Process file
    else if (entry.isFile) {
      const file = await new Promise<File>((resolve, reject) => {
        (entry as FileSystemFileEntry).file(
          (file) => resolve(file),
          (error) => {
            console.error('Error getting file:', error);
            reject(error);
          }
        );
      });

      // Add the file with its path
      const fileWithPath = new File([file], entry.fullPath, { type: file.type });
      // webkitRelativePath is read-only, so we need to use Object.defineProperty
      Object.defineProperty(fileWithPath, 'webkitRelativePath', {
        value: entry.fullPath,
        writable: false,
        configurable: true,
      });

      return [fileWithPath];
    }

    return [];
  };

  // Process directory and its contents recursively
  const processDirectory = async (directory: FileSystemDirectoryEntry, path = ''): Promise<File[]> => {
    const dirReader = directory.createReader();

    // Read directory entries
    const entries = await new Promise<FileSystemEntry[]>((resolve, reject) => {
      dirReader.readEntries(
        (entries) => resolve(Array.from(entries)),
        (error) => {
          console.error('Error reading directory:', error);
          reject(error);
        }
      );
    });

    const files: File[] = [];

    // Process each entry
    for (const entry of entries) {
      try {
        if (entry.isFile) {
          const file = await new Promise<File>((resolve, reject) => {
            (entry as FileSystemFileEntry).file(
              (file) => {
                // Create a new File object with the full path
                const filePath = path ? `${path}/${file.name}` : file.name;
                const fileWithPath = new File([file], filePath, { type: file.type });
                // webkitRelativePath is read-only, so we need to use Object.defineProperty
                Object.defineProperty(fileWithPath, 'webkitRelativePath', {
                  value: filePath,
                  writable: false,
                  configurable: true,
                });
                resolve(fileWithPath);
              },
              (error) => reject(error)
            );
          });
          files.push(file);
        } else if (entry.isDirectory) {
          const dirFiles = await processDirectory(
            entry as FileSystemDirectoryEntry,
            path ? `${path}/${entry.name}` : entry.name
          );
          files.push(...dirFiles);
        }
      } catch (error) {
        console.error(`Error processing ${entry.name}:`, error);
      }
    }

    return files;
  };

  const uploadFiles = async (files: FileList | File[] | DataTransferItemList) => {
    setUploading(true);
    const token = localStorage.getItem("token");
    const api = process.env.NEXT_PUBLIC_API_URL;

    try {
      let allFiles: File[] = [];

      // Convert input to File[]
      if (Array.isArray(files)) {
        allFiles = files.filter(item => item instanceof File) as File[];
      } else if ('length' in files) {
        // Handle FileList or DataTransferItemList
        for (let i = 0; i < files.length; i++) {
          const item = files[i];
          if (item instanceof File) {
            allFiles.push(item);
          }
        }
      }

      if (allFiles.length === 0) {
        throw new Error('No valid files found for upload');
      }

      // Upload all files with their paths
      for (const file of allFiles) {
        const formData = new FormData();
        // Use the file's path (including folder structure) for file_path
        const relativePath = file.webkitRelativePath || '';
        const directoryPath = relativePath.includes('/')
          ? relativePath.substring(0, relativePath.lastIndexOf('/') + 1)
          : '';

        // Check file size (warn if over 500MB)
        const maxSize = 500 * 1024 * 1024; // 500MB
        if (file.size > maxSize) {
          console.warn(`File ${file.name} is ${(file.size / 1024 / 1024).toFixed(2)}MB, which may exceed server limits`);
        }

        formData.append("file", file);
        formData.append("user_id", userId);
        formData.append("file_path", directoryPath);

        try {
          const controller = new AbortController();
          const timeoutId = setTimeout(() => controller.abort(), 300000); // 5 minute timeout

          const response = await fetch(`${api}/api/media-files`, {
            method: "POST",
            headers: {
              "Accept": "application/json",
              "Authorization": `Bearer ${token}`
            },
            body: formData,
            signal: controller.signal,
          });

          clearTimeout(timeoutId);

          if (!response.ok) {
            let errorData: any = {};
            try {
              errorData = await response.json();
            } catch (e) {
              const text = await response.text();
              errorData = { raw: text };
            }
            console.error(`Upload error for ${file.name}:`, {
              status: response.status,
              statusText: response.statusText,
              data: errorData,
              fileSize: file.size,
              fileType: file.type
            });
            throw new Error(`Upload failed (${response.status}): ${errorData.message || errorData.raw || file.name}`);
          }

          const result = await response.json();
          console.log('Upload successful:', result);
          if (onUploadSuccess) onUploadSuccess();
        } catch (error: any) {
          console.error(`Error uploading ${file.name}:`, error);
          // Continue with next file even if one fails
          if (error.name === 'AbortError') {
            alert(`Upload timeout for ${file.name}. File may be too large.`);
          }
        }
      }

      if (onUploadSuccess) onUploadSuccess();
    } catch (err) {
      console.error("Upload error:", err);
      alert("Upload failed! Check console for details.");
    } finally {
      setUploading(false);
    }
  };

  return (
    <AnimatePresence>
      {(showOverlay || uploading) && (
        <motion.div
          className="fixed inset-0 z-50 flex items-center justify-center backdrop-blur-sm "
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          exit={{ opacity: 0 }}
          transition={{ duration: 0.3 }}
        >
          <motion.div
            className="relative text-2xl backdrop-blur-lg rounded-2xl px-10 py-6 shadow-2xl border-2 border-primary/70"
            initial={{ opacity: 0, y: 50, scale: 0.9 }}
            animate={{ opacity: 1, y: 0, scale: 1 }}
            exit={{ opacity: 0, y: -40, scale: 0.95 }}
            transition={{ type: "spring", stiffness: 180, damping: 14 }}
          >
            <motion.div
              animate={{
                y: [0, -4, 0],
              }}
              transition={{
                duration: 2.5,
                repeat: Infinity,
                ease: "easeInOut",
              }}
              className="flex items-center gap-3 font-semibold"
            >
              {uploading ? (
                <>
                  <div className="w-5 h-5 border-4 border-primary border-t-transparent rounded-full animate-spin" />
                  Uploading...
                </>
              ) : (
                <>
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    className="w-6 h-6 text-primary"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    strokeWidth={2}
                  >
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1M12 12V4m0 8l-3-3m3 3l3-3"
                    />
                  </svg>
                  Drop files to upload
                </>
              )}
            </motion.div>

            <motion.div
              className="absolute inset-0 rounded-2xl border-2 border-primary/50 blur-sm"
              animate={{
                opacity: [0.3, 0.7, 0.3],
              }}
              transition={{
                duration: 2,
                repeat: Infinity,
                ease: "easeInOut",
              }}
            />
          </motion.div>
        </motion.div>
      )}
    </AnimatePresence>
  );
}
