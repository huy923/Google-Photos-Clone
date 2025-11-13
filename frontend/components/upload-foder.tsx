"use client"
import { useState, useRef, useEffect, forwardRef, useImperativeHandle, ChangeEvent } from "react"

interface UploadFolderProps {
  onChange?: (event: ChangeEvent<HTMLInputElement>) => void;
}

export const UploadFolder = forwardRef<HTMLInputElement, UploadFolderProps>(({ onChange }, ref) => {
    const [isDragging, setIsDragging] = useState(false)
    const fileInputRef = useRef<HTMLInputElement>(null)
    return (
        <div>
            <input type="file" multiple accept="folder/*" ref={fileInputRef} onChange={onChange} />
            
        </div>
    )
})

UploadFolder.displayName = "UploadFolder"