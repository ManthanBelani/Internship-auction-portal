class UserModel {
  final int id;
  final String name;
  final String email;
  final String? role;
  final String? profileImage;

  UserModel({
    required this.id,
    required this.name,
    required this.email,
    this.role,
    this.profileImage,
  });

  factory UserModel.fromJson(Map<String, dynamic> json) {
    return UserModel(
      id: json['id'] ?? json['userId'] ?? 0,
      name: json['name'] ?? '',
      email: json['email'] ?? '',
      role: json['role'] ?? 'buyer',
      profileImage: json['profile_image'] ?? json['profileImage'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'email': email,
      'role': role,
      'profile_image': profileImage,
    };
  }
}
