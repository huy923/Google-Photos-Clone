export interface User {
  id: number;
  name: string;
  email: string;
  email_verified_at?: string;
  avatar?: string;
  is_active: boolean;
  last_login_at?: string;
  created_at: string;
  updated_at: string;
  profile?: Profile;
  storage?: UserStorage;
  media_files?: MediaFile[];
  albums?: Album[];
  shares?: Share[];
  notifications?: Notification[];
}

export interface Profile {
  id: number;
  user_id: number;
  first_name?: string;
  last_name?: string;
  bio?: string;
  phone?: string;
  birth_date?: string;
  location?: string;
  website?: string;
  preferences?: any;
  created_at: string;
  updated_at: string;
  user?: User;
}

export interface UserStorage {
  id: number;
  user_id: number;
  used_storage: number;
  max_storage: number;
  file_count: number;
  created_at: string;
  updated_at: string;
  user?: User;
}

export interface MediaFile {
  id: number;
  user_id: number;
  original_name: string;
  filename: string;
  file_path: string;
  thumbnail_path?: string;
  public_url: string;
  mime_type: string;
  file_type: "image" | "video" | "gif" | "document" | "audio";
  file_size: number;
  width?: number;
  height?: number;
  duration?: number;
  is_processed: boolean;
  is_optimized: boolean;
  is_deleted: boolean;
  deleted_at?: string;
  created_at: string;
  updated_at: string;
  user?: User;
  metadata?: MediaMetadata;
  tags?: MediaTag[];
  albums?: Album[];
  comments?: MediaComment[];
  favorites?: MediaFavorite[];
  views?: MediaView[];
  shares?: Share[];
}

export interface MediaMetadata {
  id: number;
  media_file_id: number;
  taken_at?: string;
  camera_make?: string;
  camera_model?: string;
  lens_model?: string;
  latitude?: number;
  longitude?: number;
  location_name?: string;
  city?: string;
  country?: string;
  altitude?: number;
  focal_length?: number;
  aperture?: number;
  shutter_speed?: string;
  iso?: number;
  flash?: boolean;
  white_balance?: string;
  exif_data?: any;
  created_at: string;
  updated_at: string;
  media_file?: MediaFile;
}

export interface Album {
  id: number;
  user_id: number;
  name: string;
  description?: string;
  cover_photo_path?: string;
  type: "manual" | "auto";
  auto_criteria?: any;
  is_public: boolean;
  is_deleted: boolean;
  deleted_at?: string;
  created_at: string;
  updated_at: string;
  user?: User;
  media_files?: MediaFile[];
  shares?: Share[];
}

export interface MediaTag {
  id: number;
  name: string;
  slug: string;
  color?: string;
  created_at: string;
  updated_at: string;
  media_files_count?: number;
}

export interface MediaComment {
  id: number;
  media_file_id: number;
  user_id: number;
  comment: string;
  parent_id?: number;
  is_edited: boolean;
  created_at: string;
  updated_at: string;
  user?: User;
  media_file?: MediaFile;
  parent?: MediaComment;
  replies?: MediaComment[];
}

export interface MediaFavorite {
  id: number;
  media_file_id: number;
  user_id: number;
  created_at: string;
  updated_at: string;
  user?: User;
  media_file?: MediaFile;
}

export interface MediaView {
  id: number;
  media_file_id: number;
  user_id?: number;
  ip_address?: string;
  user_agent?: string;
  created_at: string;
  updated_at: string;
  user?: User;
  media_file?: MediaFile;
}

export interface Share {
  id: number;
  user_id: number;
  share_token: string;
  shareable_type: "App\Models\MediaFile" | "App\Models\Album";
  shareable_id: number;
  permission: "view" | "download" | "edit";
  access_type: "public" | "private" | "password";
  expires_at?: string;
  view_count: number;
  is_active: boolean;
  created_at: string;
  updated_at: string;
  user?: User;
  shareable?: MediaFile | Album;
  share_access?: ShareAccess[];
}

export interface ShareAccess {
  id: number;
  share_id: number;
  user_id?: number;
  email?: string;
  permission: "view" | "download" | "edit";
  accessed_at?: string;
  created_at: string;
  updated_at: string;
  share?: Share;
  user?: User;
}

export interface Notification {
  id: number;
  user_id: number;
  type: string;
  title: string;
  message: string;
  data?: any;
  is_read: boolean;
  read_at?: string;
  created_at: string;
  updated_at: string;
  user?: User;
}