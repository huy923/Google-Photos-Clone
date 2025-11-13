"use client";
import { useEffect, useState } from "react";
import { apiClient } from "@/lib/api-client";

interface MediaTagWithFiles {
  id: number;
  name: string;
  color?: string;
  media_files?: any[];
}

export default function TagsPage() {
  const [tags, setTags] = useState<MediaTagWithFiles[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    async function fetchTags() {
      try {
        const result = await apiClient.get("/media-tags");
        const tagsWithFiles = await Promise.all((result.data || result).map(async (tag: any) => {
          let fileResult = { data: [] };
          try {
            fileResult = await apiClient.get(`/media-files?tag_id=${tag.id}`);
          } catch {}
          return { ...tag, media_files: fileResult.data || [] };
        }));
        setTags(tagsWithFiles);
      } catch {
        setTags([]);
      } finally {
        setLoading(false);
      }
    }
    fetchTags();
  }, []);

  if (loading) return <div className="p-8">?ang t?i th? ...</div>;

  return (
    <div className="p-8">
      <h1 className="text-3xl font-bold mb-8">Danh s?ch th? v? media</h1>
      {tags.length ? (
        <div>
          {tags.map(tag => (
            <div key={tag.id} className="mb-8 border rounded-lg border-border p-4">
              <div className="flex items-center mb-2">
                <span className="mr-2" style={{ backgroundColor: tag.color || '#aaa', width: 16, height: 16, display: 'inline-block', borderRadius: '50%' }}></span>
                <span className="font-medium">{tag.name}</span>
              </div>
              <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                {tag.media_files && tag.media_files.length > 0 ? (
                  tag.media_files.map(file => (
                    <div className="border rounded p-3 text-sm" key={file.id}>
                      {file.original_name}<br/>
                      <span className="text-muted-foreground">{file.file_type}</span>
                    </div>
                  ))
                ) : (
                  <div className="text-muted-foreground">Kh?ng c? file n?o</div>
                )}
              </div>
            </div>
          ))}
        </div>
      ) : <div>Kh?ng c? th? n?o.</div>}
    </div>
  );
}
