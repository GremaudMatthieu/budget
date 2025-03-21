import React from "react";

interface ModalProps {
    isOpen: boolean;
    onClose: () => void;
    children: React.ReactNode;
}

export function Modal({ isOpen, onClose, children }: ModalProps) {
    if (!isOpen) return null;

    return (
        <>
            <div
                className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
                onClick={onClose}
            >
                <div
                    className="bg-white p-6 rounded-lg max-w-4xl max-h-[80vh] overflow-y-auto relative"
                    onClick={(e) => e.stopPropagation()}
                >
                    {children}
                </div>
            </div >
        </>
    );
}
