"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { ThemeToggle } from "./theme-toggle";
import { Button } from "./ui/button";
import { Menu, X, Home, Folder, Image, Settings, LogOut, User, ImageUpIcon, Trash } from "lucide-react";
import { useState, useEffect } from "react";
import { cn } from "@/lib/utils";

const menuItems = [
  { name: "Home", href: "/", icon: Home },
  { name: "Albums", href: "/albums", icon: Folder },
  { name: "Photos", href: "/photos", icon: Image },
  { name: "Settings", href: "/settings", icon: Settings },
  { name: "Friends", href: "/friends", icon: User },
  { name: "Bin", href: "/bin", icon: Trash },
  { name: "Logout", href: "#", icon: LogOut, isLogout: true },
];

export default function Header() {
  const router = useRouter();
  const [isOpen, setIsOpen] = useState(false);

  const handleLogout = async () => {
    try {
      if (typeof window !== "undefined") {
        localStorage.removeItem("user");
        localStorage.removeItem("token");
        localStorage.removeItem("userId");
      }

      router.replace("/login");
    } catch (error) {
      console.error("Logout failed:", error);
      router.replace("/login");
    }
  };

  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      const target = event.target as HTMLElement;
      if (
        isOpen &&
        !target.closest(".menu-container") &&
        !target.closest(".menu-button")
      ) {
        setIsOpen(false);
      }
    };

    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, [isOpen]);

  return (
    <>
      {/* Overlay */}
      <div
        className={cn(
          "fixed inset-0 bg-black/50 z-40 transition-opacity",
          isOpen ? "opacity-100" : "opacity-0 pointer-events-none"
        )}
        onClick={() => setIsOpen(false)}
      />

      {/* Sidebar Menu */}
      <div
        className={cn(
          "fixed top-0 left-0 h-full w-64 bg-background border-r border-border z-50 transform transition-transform duration-300 ease-in-out menu-container",
          isOpen ? "translate-x-0" : "-translate-x-full"
        )}
      >
        <div className="p-4 border-b border-border flex justify-between items-center">
          <h2 className="text-xl font-bold">Menu</h2>
          <Button
            variant="ghost"
            size="icon"
            onClick={() => setIsOpen(false)}
            className="rounded-full"
          >
            <X className="h-5 w-5" />
          </Button>
        </div>
        <nav className="p-2">
          {menuItems.map((item) => {
            const Icon = item.icon;
            if (item.isLogout) {
              return (
                <button
                  key={item.name}
                  onClick={() => {
                    setIsOpen(false);
                    handleLogout();
                  }}
                  className="w-full flex items-center px-4 py-3 rounded-lg hover:bg-accent text-foreground hover:text-accent-foreground transform transition-transform mb-1 duration-300 ease-in-out"
                >
                  <Icon className="h-5 w-5 mr-3" />
                  <span>{item.name}</span>
                </button>
              );
            }

            return (
              <Link
                key={item.name}
                href={item.href}
                onClick={() => setIsOpen(false)}
                className="flex items-center px-4 py-3 rounded-lg hover:bg-accent text-foreground hover:text-accent-foreground transform transition-transform mb-1 duration-300 ease-in-out"
              >
                <Icon className="h-5 w-5 mr-3" />
                <span>{item.name}</span>
              </Link>
            );
          })}
        </nav>
      </div>

      {/* Header */}
      <div className="flex justify-between items-center h-16 border-b border-border sticky top-0 bg-background/95 backdrop-blur supports-backdrop-filter:bg-background/60 z-30">
        <div className="flex items-center gap-2 ml-3">
          <Button
            className="rounded-full menu-button"
            variant="ghost"
            size="icon"
            onClick={() => setIsOpen(!isOpen)}>
            <Menu />
          </Button>
          <Link href="/" className="text-xl font-bold">
            Photo Gallery
          </Link>
        </div>
        <div className="flex gap-2 items-center m-3">
          <Button variant={"outline"} size={"icon"} className="">
            <ImageUpIcon ></ImageUpIcon>
          </Button>
          <ThemeToggle />
        </div>
      </div>
    </>
  );
}
